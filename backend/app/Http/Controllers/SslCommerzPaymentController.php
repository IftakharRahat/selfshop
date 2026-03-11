<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Admin;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class SslCommerzPaymentController extends Controller
{
    private function frontendBaseUrl(): string
    {
        $base = env('FRONTEND_URL')
            ?: config('app.frontend_url')
            ?: env('CLIENT_URL')
            ?: env('APP_FRONTEND_URL')
            ?: 'http://localhost:3000';

        return rtrim($base, '/');
    }

    private function frontendUrl(string $path = '/', array $query = []): string
    {
        $url = $this->frontendBaseUrl() . '/' . ltrim($path, '/');
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '';
        });

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function resolveNextExpiryDate(?string $currentExpireDate = null): string
    {
        $today = date('Y-m-d');
        $baseDate = $today;

        if (!empty($currentExpireDate) && $currentExpireDate >= $today) {
            $baseDate = $currentExpireDate;
        }

        return date('Y-m-d', strtotime($baseDate . ' +1 year'));
    }

    public function index(Request $request)
    {
        try {
            # Validate required data from the request
            $validated = $request->validate([
                'customerName' => 'required|string|max:255',
                'customerPhone' => 'required|string|max:20',
                'customerAddress' => 'required|string',
                'deliveryCharge' => 'required|numeric|min:0',
                'subTotal' => 'required|numeric|min:0',
                'user_id' => 'nullable|integer',
                'customerNote' => 'nullable|string',
                'blance_from' => 'nullable|string',
            ]);
            
            # Check if cart is empty
            if (Cart::count() == 0) {
                return redirect()->back()->withErrors([
                    'error' => 'Your cart is empty. Please add products before checkout.'
                ]);
            }
            
            # Calculate total amount
            $subTotal = $request->subTotal;
            $deliveryCharge = $request->deliveryCharge;
            $total_amount = $subTotal + $deliveryCharge;
            
            # Generate unique transaction ID
            $tran_id = 'SELFSHOP' . time() . uniqid();
            $currency = "BDT";
            
            # Get cart content for later use
            $cartContent = Cart::content();
            
            # Get user email if logged in
            $userEmail = Auth::check() ? Auth::user()->email : 'customer@selfshop.com';
            
            # Prepare payment data for SSLCommerz
            $post_data = array();
            $post_data['total_amount'] = $total_amount;
            $post_data['currency'] = $currency;
            $post_data['tran_id'] = $tran_id;

            # CUSTOMER INFORMATION
            $post_data['cus_name'] = $request->customerName;
            $post_data['cus_email'] = $userEmail;
            $post_data['cus_add1'] = $request->customerAddress;
            $post_data['cus_add2'] = "";
            $post_data['cus_city'] = "";
            $post_data['cus_state'] = "";
            $post_data['cus_postcode'] = "";
            $post_data['cus_country'] = "Bangladesh";
            $post_data['cus_phone'] = $request->customerPhone;
            $post_data['cus_fax'] = "";

            # SHIPMENT INFORMATION
            $post_data['ship_name'] = $request->customerName;
            $post_data['ship_add1'] = $request->customerAddress;
            $post_data['ship_add2'] = "";
            $post_data['ship_city'] = "";
            $post_data['ship_state'] = "";
            $post_data['ship_postcode'] = "";
            $post_data['ship_phone'] = $request->customerPhone;
            $post_data['ship_country'] = "Bangladesh";

            $post_data['shipping_method'] = "Courier";
            $post_data['product_name'] = "Products from Selfshop";
            $post_data['product_category'] = "Mixed";
            $post_data['product_profile'] = "physical-goods";

            # OPTIONAL PARAMETERS - Store additional info for callback
            $post_data['value_a'] = $request->user_id ?? '0'; // User ID
            $post_data['value_b'] = session()->getId(); // Session ID
            $post_data['value_c'] = json_encode($cartContent); // Cart data as JSON
            $post_data['value_d'] = $request->customerNote ?? ''; // Customer note

            # Store initial order information in database
            $orderId = DB::table('orders')->insertGetId([
                'name' => $post_data['cus_name'],
                'email' => $post_data['cus_email'],
                'phone' => $post_data['cus_phone'],
                'amount' => $post_data['total_amount'],
                'subTotal' => $subTotal,
                'deliveryCharge' => $deliveryCharge,
                'status' => 'Pending',
                'address' => $post_data['cus_add1'],
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency'],
                'user_id' => $request->user_id ?? null,
                'customer_note' => $request->customerNote ?? null,
                'data' => json_encode([
                    'cus_name' => $request->customerName,
                    'cus_phone' => $request->customerPhone,
                    'cus_addr1' => $request->customerAddress,
                    'dv_charge' => $deliveryCharge,
                    'note' => $request->customerNote,
                    'amount' => $subTotal
                ]),
                'cart' => json_encode($cartContent),
                'payment_type' => $request->blance_from == 'online_payment' ? 'SSLCommerz' : 'Account Balance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            # If payment method is Account Balance, process immediately
            if ($request->blance_from == 'from_account' && Auth::check()) {
                $user = Auth::user();
                if ($user->account_balance >= $total_amount) {
                    # Deduct from account balance
                    DB::table('users')
                        ->where('id', $user->id)
                        ->decrement('account_balance', $total_amount);
                    
                    # Update order status
                    DB::table('orders')
                        ->where('id', $orderId)
                        ->update([
                            'status' => 'Processing',
                            'payment_status' => 'Paid',
                            'payment_type' => 'Account Balance',
                            'updated_at' => now(),
                        ]);
                    
                    # Create order products and other records
                    $this->createOrderDetails($orderId, $cartContent, $request);
                    
                    # Clear cart
                    Cart::destroy();
                    
                    # Redirect to success page
                    return redirect()->route('user.orders')->with('success', 'Order placed successfully using account balance!');
                } else {
                    # Insufficient balance
                    DB::table('orders')->where('id', $orderId)->delete();
                    return redirect()->back()->withErrors([
                        'error' => 'Insufficient account balance. Please choose another payment method.'
                    ]);
                }
            }

            # For online payment, proceed with SSLCommerz
            # Store transaction ID in session for callback
            Session::put('sslcommerz_tran_id', $tran_id);
            Session::put('sslcommerz_order_id', $orderId);
            Session::put('cart_data', $cartContent);
            Session::put('order_data', [
                'customerName' => $request->customerName,
                'customerPhone' => $request->customerPhone,
                'customerAddress' => $request->customerAddress,
                'deliveryCharge' => $deliveryCharge,
                'customerNote' => $request->customerNote,
                'user_id' => $request->user_id,
                'subTotal' => $subTotal,
            ]);

            # Initialize SSLCommerz with external redirect (not iframe)
            $sslc = new SslCommerzNotification();
            
            # Use 'hosted' mode for external redirect
            # The second parameter 'hosted' will redirect to SSLCommerz gateway in a new window
            $payment_options = $sslc->makePayment($post_data, 'hosted');
            
            # If payment initiation failed
            if (!is_array($payment_options)) {
                Log::error('SSLCommerz payment initiation failed: ' . print_r($payment_options, true));
                throw new \Exception('Payment gateway error. Please try again.');
            }
            
        } catch (\Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage());
            
            # Clean up failed order
            if (isset($orderId)) {
                DB::table('orders')->where('id', $orderId)->delete();
            }
            
            return redirect()->back()->withErrors([
                'error' => 'Checkout failed: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add these methods to your SslCommerzPaymentController class

/**
 * Initiate package payment via SSLCommerz (Hosted)
 */
public function initiatePackagePayment(Request $request)
{
    try {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'invoice_id' => 'required|exists:resellerinvoices,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'invoiceID' => 'required|string',
        ]);
        
        // Get package and invoice details
        $package = \App\Models\Package::find($request->package_id);
        $invoice = \App\Models\Resellerinvoice::find($request->invoice_id);
        $user = \App\Models\User::find($request->user_id);
        
        if (!$package || !$invoice || !$user) {
            return redirect()->back()->withErrors(['error' => 'Invalid payment request.']);
        }
        
        if ($invoice->status == 'Paid') {
            return redirect()->back()->withErrors(['error' => 'This invoice is already paid.']);
        }
        
        // Generate unique transaction ID
        $tran_id = 'PKG_' . time() . '_' . uniqid();
        $currency = "BDT";
        
        // Prepare SSLCommerz data
        $post_data = array();
        $post_data['total_amount'] = $request->amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] = $tran_id;
        
        // Customer information
        $post_data['cus_name'] = $user->name ?? 'Customer';
        $post_data['cus_email'] = $user->email ?? 'customer@selfshop.com';
        $post_data['cus_add1'] = $user->address ?? 'N/A';
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = $user->phone ?? 'N/A';
        $post_data['cus_fax'] = "";
        
        // Shipping information
        $post_data['ship_name'] = $user->name ?? 'Customer';
        $post_data['ship_add1'] = $user->address ?? 'N/A';
        $post_data['ship_add2'] = "";
        $post_data['ship_city'] = "";
        $post_data['ship_state'] = "";
        $post_data['ship_postcode'] = "";
        $post_data['ship_phone'] = $user->phone ?? 'N/A';
        $post_data['ship_country'] = "Bangladesh";
        
        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = ($package->package_name ?? 'Package') . " - Selfshop";
        $post_data['product_category'] = "Digital";
        $post_data['product_profile'] = "non-physical-goods";
        
        // Store additional data for callback
        $post_data['value_a'] = $user->id; // User ID
        $post_data['value_b'] = $invoice->id; // Invoice ID
        $post_data['value_c'] = $package->id; // Package ID
        $post_data['value_d'] = json_encode([
            'invoice_id' => $invoice->id,
            'invoice_code' => $invoice->invoiceID,
            'package_name' => $package->package_name,
            'amount' => $request->amount,
        ]);
        
        // Update invoice with transaction ID
        $invoice->payment_id = $tran_id;
        $invoice->save();
        
        // Store in session for callback
        Session::put('sslcommerz_package_payment', [
            'tran_id' => $tran_id,
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'package_id' => $package->id,
            'amount' => $request->amount,
            'invoice_code' => $invoice->invoiceID,
        ]);
        
        Log::info('SSLCommerz Package Payment Initiated:', [
            'tran_id' => $tran_id,
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => $request->amount,
        ]);
        
        // Initialize SSLCommerz
        $sslc = new SslCommerzNotification();
        
        // Use 'hosted' mode - this will redirect to SSLCommerz gateway
        $payment_options = $sslc->makePayment($post_data, 'hosted');
        
        if (!is_array($payment_options)) {
            Log::error('SSLCommerz package payment initiation failed: ' . print_r($payment_options, true));
            return redirect()->back()->withErrors(['error' => 'Payment gateway error. Please try again.']);
        }
        
        // SSLCommerz will handle the redirect automatically
        
    } catch (\Exception $e) {
        Log::error('Package payment initiation error: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Payment initiation failed: ' . $e->getMessage()]);
    }
}

/**
 * Handle SSLCommerz success callback for package payment
 */
/**
 * Handle SSLCommerz success callback for package payment
 */
public function packagePaymentSuccess(Request $request)
{
    DB::beginTransaction();
    
    try {
        Log::info('=== SSLCommerz PACKAGE Payment Success Callback ===');
        
        $tran_id = $request->input('tran_id');
        $status = $request->input('status');
        
        if (!$tran_id) {
            Log::error('No transaction ID in package success callback');
            return redirect()->away($this->frontendUrl('/pricing', [
                'payment' => 'error',
            ]));
        }
        
        // Find invoice
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
        
        if (!$invoice && $request->input('value_b')) {
            $invoice = \App\Models\Resellerinvoice::find($request->input('value_b'));
        }
        
        if (!$invoice) {
            Log::error('Invoice not found');
            DB::rollBack();
            return redirect()->away($this->frontendUrl('/pricing', [
                'payment' => 'error',
            ]));
        }
        
        // Validate payment
        $sslc = new SslCommerzNotification();
        $validation = $sslc->orderValidate($request->all(), $tran_id, 0, 'BDT');
        
        if (!$validation && $status != 'VALID') {
            Log::error('Payment validation failed');
            $invoice->status = 'Failed';
            $invoice->save();
            
            DB::rollBack();
            Session::forget('sslcommerz_package_payment');
            return redirect()->away($this->frontendUrl('/pricing', [
                'payment' => 'failed',
            ]));
        }
        
        Log::info('Payment validated successfully');
        
        // Check if already paid
        if ($invoice->status == 'Paid') {
            Log::warning('Invoice already paid');
            
            $user = \App\Models\User::find($invoice->user_id);
            if ($user && !Auth::check()) {
                Auth::login($user);
            }
            
            DB::rollBack();
            return redirect()->away($this->frontendUrl('/dashboard', [
                'payment' => 'already_paid',
                'invoiceID' => $invoice->invoiceID ?? null,
            ]));
        }
        
        // Get user
        $user = \App\Models\User::find($invoice->user_id);
        if (!$user) {
            Log::error('User not found for invoice');
            DB::rollBack();
            return redirect()->away($this->frontendUrl('/pricing', [
                'payment' => 'error',
            ]));
        }

        // Subscription validity is 1 year, extending from current expiry if still active.
        $today = date('Y-m-d');
        $expireDate = $this->resolveNextExpiryDate($user->expire_date);
        
        // ========== UPDATE INVOICE ==========
        $invoice->paymentDate = $today;
        $invoice->paid_amount = $request->input('amount');
        $invoice->payment_type = 'SSLCommerz';
        $invoice->status = 'Paid';
        $invoice->payment_id = $tran_id;
        $invoice->expire_date = $expireDate;
        $invoice->save();
        
        Log::info('Invoice updated as paid:', [
            'invoice_id' => $invoice->id,
            'expire_date' => $invoice->expire_date
        ]);
        
        // ========== CRITICAL: UPDATE USERS TABLE ==========
        // Direct database update to ensure users table is updated
        $userUpdateResult = DB::table('users')
            ->where('id', $user->id)
            ->update([
                'status' => 'Active',
                'membership_status' => 'Paid',
                'active_date' => $today,
                'expire_date' => $expireDate, // This updates the users.expire_date column
                'p_system' => 'Gateway',
                'updated_at' => now(),
            ]);
        
        Log::info('USERS TABLE UPDATE RESULT:', [
            'user_id' => $user->id,
            'rows_affected' => $userUpdateResult,
            'expire_date_set' => $expireDate,
            'active_date_set' => $today
        ]);
        
        // Verify the update
        $updatedUser = DB::table('users')->where('id', $user->id)->first();
        Log::info('USERS TABLE VERIFICATION:', [
            'user_id' => $updatedUser->id,
            'current_status' => $updatedUser->status,
            'current_expire_date' => $updatedUser->expire_date,
            'current_active_date' => $updatedUser->active_date
        ]);
        
        // Give referral bonus (only if user was not already active)
        if ($user->status != 'Active' && !empty($user->refer_by)) {
            $referuser = \App\Models\User::where('my_referral_code', $user->refer_by)->first();
            $refbonus = 200;
            
            if ($referuser) {
                $referuser->referal_bonus += $refbonus;
                $referuser->account_balance += $refbonus;
                $referuser->save();
                
                $message = new \App\Models\Message();
                $message->user_id = $referuser->id;
                $message->message_for = 'Referral Bonus';
                $message->message = 'You Get ' . $refbonus . ' TK As Your Referral Bonus';
                $message->amount = $refbonus;
                $message->date = $today;
                $message->save();
            }
        }
        
        // Auto-login user
        if (!Auth::check()) {
            Auth::login($user);
            Log::info('User auto-logged in:', ['user_id' => $user->id]);
        }
        
        // Commit transaction
        DB::commit();
        
        // Clear session
        Session::forget('sslcommerz_package_payment');
        
        // Redirect with success
        return redirect()->away($this->frontendUrl('/dashboard', [
            'payment' => 'success',
            'invoiceID' => $invoice->invoiceID ?? null,
            'expire_date' => $expireDate,
        ]));
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Package payment error: ' . $e->getMessage());
        Log::error('Error at line: ' . $e->getLine());
        
        // Check if it's the WebviewController error
        if (strpos($e->getFile(), 'WebviewController.php') !== false) {
            Log::warning('WebviewController error detected, but payment was processed');
            
            // Try to save invoice status if it failed mid-way
            if (isset($invoice) && $invoice && $invoice->status != 'Paid') {
                try {
                    $invoice->status = 'Paid';
                    $invoice->save();
                    
                    // Also try to update user
                    if (isset($user) && $user) {
                        $expireDate = $this->resolveNextExpiryDate($user->expire_date);
                        DB::table('users')->where('id', $user->id)->update([
                            'status' => 'Active',
                            'expire_date' => $expireDate,
                            'active_date' => date('Y-m-d')
                        ]);
                    }
                } catch (\Exception $e2) {
                    Log::error('Recovery save failed: ' . $e2->getMessage());
                }
            }
            
            return redirect()->away($this->frontendUrl('/dashboard', [
                'payment' => 'success',
            ]));
        }
        
        return redirect()->away($this->frontendUrl('/pricing', [
            'payment' => 'error',
        ]));
    }
}


/**
 * Handle SSLCommerz fail callback for package payment
 */
public function packagePaymentFail(Request $request)
{
    Log::info('SSLCommerz Package Payment Fail Callback:', $request->all());
    
    $tran_id = $request->input('tran_id');
    $invoice = null;
    
    if ($tran_id) {
        // Update invoice status to Failed
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
    }

    if (!$invoice && $request->filled('value_b')) {
        $invoice = \App\Models\Resellerinvoice::find((int) $request->input('value_b'));
    }

    if ($invoice && strcasecmp((string) $invoice->status, 'Paid') !== 0) {
            $invoice->status = 'Failed';
            $invoice->save();
            Log::info('Invoice marked as failed:', ['invoice_id' => $invoice->id]);
    }
    
    Session::forget('sslcommerz_package_payment');

    if ($invoice) {
        return redirect()->away($this->frontendUrl('/invoice', [
            'invoice_id' => $invoice->id,
            'invoiceID' => $invoice->invoiceID,
            'package_id' => $invoice->package_id,
            'payment' => 'failed',
        ]));
    }

    return redirect()->away($this->frontendUrl('/pricing', [
        'payment' => 'failed',
    ]));
}

/**
 * Handle SSLCommerz cancel callback for package payment
 */
public function packagePaymentCancel(Request $request)
{
    Log::info('SSLCommerz Package Payment Cancel Callback:', $request->all());
    
    $tran_id = $request->input('tran_id');
    $invoice = null;
    
    if ($tran_id) {
        // Update invoice status to Canceled
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
    }

    if (!$invoice && $request->filled('value_b')) {
        $invoice = \App\Models\Resellerinvoice::find((int) $request->input('value_b'));
    }

    if ($invoice && strcasecmp((string) $invoice->status, 'Paid') !== 0) {
            $invoice->status = 'Canceled';
            $invoice->save();
            Log::info('Invoice marked as canceled:', ['invoice_id' => $invoice->id]);
    }
    
    Session::forget('sslcommerz_package_payment');

    if ($invoice) {
        return redirect()->away($this->frontendUrl('/invoice', [
            'invoice_id' => $invoice->id,
            'invoiceID' => $invoice->invoiceID,
            'package_id' => $invoice->package_id,
            'payment' => 'canceled',
        ]));
    }

    return redirect()->away($this->frontendUrl('/pricing', [
        'payment' => 'canceled',
    ]));
}

/**
 * Handle SSLCommerz IPN for package payment
 */
public function packagePaymentIPN(Request $request)
{
    Log::info('SSLCommerz Package Payment IPN Received:', $request->all());
    
    if ($request->input('tran_id')) {
        $tran_id = $request->input('tran_id');
        $status = $request->input('status');
        
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
        
        if ($invoice && $invoice->status == 'Pending') {
            if ($status == 'VALID') {
                // Update invoice status via IPN
                $invoice->status = 'Paid';
                $invoice->paymentDate = date('Y-m-d');
                $invoice->paid_amount = $request->input('amount', 0);
                $invoice->payment_type = 'SSLCommerz';
                $user = \App\Models\User::find($invoice->user_id);
                $expireDate = $this->resolveNextExpiryDate($user ? $user->expire_date : null);
                $invoice->expire_date = $expireDate;
                $invoice->save();
                
                // Activate user
                if ($user) {
                    $user->status = 'Active';
                    $user->membership_status = 'Paid';
                    $user->active_date = date('Y-m-d');
                    $user->expire_date = $expireDate;
                    $user->p_system = 'Gateway';
                    $user->save();
                    
                    // Give referral bonus
                    $referuser = \App\Models\User::where('my_referral_code', $user->refer_by)->first();
                    if ($referuser) {
                        $refbonus = 200;
                        $referuser->referal_bonus += $refbonus;
                        $referuser->account_balance += $refbonus;
                        $referuser->save();
                    }
                }
                
                Log::info('Package payment processed via IPN:', ['invoice_id' => $invoice->id]);
                echo "Package payment validated and updated via IPN";
            } else {
                echo "IPN validation failed";
            }
        } else {
            echo "Invoice already processed or invalid";
        }
    } else {
        echo "Invalid IPN data";
    }
}
    
      public function payViaAjax(Request $request)
    {
        $shopproducts = Cart::content()->groupBy('weight');
        $shop = count($shopproducts);
        $data = json_decode($request->cart_json);
        $chargeamount = $shop * $data->dv_charge;
        $post_data = array();
        $post_data['total_amount'] = $chargeamount; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); // tran_id must be unique

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $data->cus_name;
        $post_data['cus_email'] = 'customer@mail.com';
        $post_data['cus_add1'] = $data->cus_addr1;
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] =  $data->cus_phone;
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        $post_data['ship_name'] = $data->cus_name;
        $post_data['ship_add1'] = $data->cus_addr1;
        $post_data['ship_add2'] = "";
        $post_data['ship_city'] = "";
        $post_data['ship_state'] = "";
        $post_data['ship_postcode'] = "";
        $post_data['ship_phone'] = "";
        $post_data['ship_country'] = "Bangladesh";

        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = "Products";
        $post_data['product_category'] = "Goods";
        $post_data['product_profile'] = "physical-goods";

        #Before  going to initiate the payment order status need to update as Pending.
        $update_product = DB::table('orders')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'store_id' => 1,
                'invoiceID' => $this->uniqueID(),
                'subTotal' => $data->amount,
                'deliveryCharge' => $data->dv_charge,
                'data' => json_encode($request),
                'cart' => json_encode($shopproducts),
                'orderDate' => date('Y-m-d'),
                'courier_id' => 26,
                'transaction_id' => $post_data['tran_id'],
                'user_id' => Auth::id(),

            ]);

        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');

        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }
    }



public function initiatePayment(Request $request)
{
    try {
        // Debug: Log what's coming in
        Log::info('SSLCommerz Payment Initiation - Request Data:', $request->all());
        
        // More flexible validation
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'required|string',
            'delivery_charge' => 'required|numeric|min:0',
            'user_id' => 'nullable|integer',
            'customer_note' => 'nullable|string',
        ]);
        
        // Check if cart is empty
        if (Cart::count() == 0) {
            return redirect()->back()->withErrors(['error' => 'Your cart is empty.']);
        }
        
        // Get or generate email
        $customerEmail = $request->customer_email;
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            // Generate a valid email in format: number@selfshop.com.bd
            $phone = $request->customer_phone;
            if ($phone && preg_match('/\d+/', $phone, $matches)) {
                // Extract numbers from phone
                $phoneNumbers = preg_replace('/\D/', '', $phone);
                $uniqueNumber = substr($phoneNumbers, -8); // Get last 8 digits
                $customerEmail = $uniqueNumber . '@selfshop.com.bd';
            } else {
                // Fallback to timestamp
                $customerEmail = 'cust' . time() . '@selfshop.com.bd';
            }
        }
        
        // Calculate totals from cart LIKE payViaAjax
        $cartContent = Cart::content();
        
        // Group by store (weight) to count number of shops
        $shopProducts = Cart::content()->groupBy('weight');
        $shopCount = count($shopProducts);
        
        // Get subtotal from cart
        $subTotal = Cart::subtotalFloat();
        $deliveryCharge = $request->delivery_charge;
        
        // Calculate profit: selling price - reseller buy price
        $profit = 0;
        $orderBonus = 0;
        foreach ($cartContent as $item) {
            $productId = $item->options->product_id ?? $item->id;
            $product = DB::table('products')->where('id', $productId)->first();
            if ($product) {
                $buyPrice = (float) ($product->ProductResellerPrice ?? 0);
                $sellPrice = (float) $item->price;
                $profit += ($sellPrice - $buyPrice) * $item->qty;
                $orderBonus += (float) ($product->reseller_bonus ?? 0);
            }
        }
        
        // Calculate total amount LIKE payViaAjax: shop count * delivery charge
        $totalAmount = $shopCount * $deliveryCharge;
        
        // Generate unique transaction ID
        $tran_id = 'SELFSHOP_' . time() . '_' . uniqid();
        $currency = "BDT";
        
        // Prepare SSLCommerz data
        $post_data = array();
        $post_data['total_amount'] = $totalAmount; // Only delivery charges
        $post_data['currency'] = $currency;
        $post_data['tran_id'] = $tran_id;
        
        // Customer information
        $post_data['cus_name'] = $request->customer_name;
        $post_data['cus_email'] = $customerEmail;
        $post_data['cus_add1'] = $request->customer_address;
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = $request->customer_phone;
        $post_data['cus_fax'] = "";
        
        // Shipping information
        $post_data['ship_name'] = $request->customer_name;
        $post_data['ship_add1'] = $request->customer_address;
        $post_data['ship_add2'] = "";
        $post_data['ship_city'] = "";
        $post_data['ship_state'] = "";
        $post_data['ship_postcode'] = "";
        $post_data['ship_phone'] = $request->customer_phone;
        $post_data['ship_country'] = "Bangladesh";
        
        $post_data['shipping_method'] = "Courier";
        $post_data['product_name'] = "Order from Selfshop";
        $post_data['product_category'] = "Mixed";
        $post_data['product_profile'] = "physical-goods";
        
        // Store additional data for callback - IMPORTANT for session recovery
 $post_data['value_a'] = $request->user_id ?? '0';
        $post_data['value_b'] = session()->getId(); // Store session ID
        $post_data['value_c'] = $deliveryCharge; // Per shop delivery charge
        $post_data['value_d'] = json_encode([
            'shop_count' => $shopCount,
            'subtotal' => $subTotal,
            'customer_note' => $request->customer_note ?? '',
            'user_id' => $request->user_id ?? '0', // Store user_id here too
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address
        ]);
        
        
        Log::info('Payment calculation:', [
            'shop_count' => $shopCount,
            'delivery_charge_per_shop' => $deliveryCharge,
            'total_amount' => $totalAmount,
            'cart_subtotal' => $subTotal
        ]);
        
        // Create initial order record
        $orderId = DB::table('orders')->insertGetId([
            'invoiceID' => $this->uniqueID(),
            'user_id' => $request->user_id ?? null,
            'customerNote' => $request->customer_note ?? null,
            'courier_id' => 27, // Carry Bee courier
            'subTotal' => $subTotal,
            'profit' => $profit,
            'order_bonus' => $orderBonus,
            'deliveryCharge' => $deliveryCharge,
            'paymentAmount' => $totalAmount, // Store delivery charge total
            'orderDate' => date('Y-m-d'),
            'status' => 'Pending',
            'admin_id' => 1, // Default admin/store ID
            'store_id' => 1, // Default store
            'payment_type_id' => 6, // SSLCommerz
            'transaction_id' => $post_data['tran_id'],
            'data' => json_encode([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $customerEmail,
                'customer_address' => $request->customer_address,
                'payment_type' => 'SSLCommerz',
                'currency' => $currency,
                'shop_count' => $shopCount,
                'delivery_charge_per_shop' => $deliveryCharge,
                'total_delivery_charge' => $totalAmount,
                'cart_subtotal' => $subTotal,
                'customer_note' => $request->customer_note ?? '',
                'sslcommerz_data' => $post_data,
                'session_id' => session()->getId()
            ]),
            'cart' => json_encode($cartContent),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Store in session for callback
        Session::put('sslcommerz_tran_id', $tran_id);
        Session::put('sslcommerz_order_id', $orderId);
        Session::put('cart_data', $cartContent);
        Session::put('order_data', [
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'delivery_charge' => $deliveryCharge,
            'customer_note' => $request->customer_note,
            'user_id' => $request->user_id,
            'sub_total' => $subTotal,
            'shop_count' => $shopCount,
        ]);
        
        // Also store user info in session for recovery after payment
        if (Auth::check()) {
            Session::put('payment_user_id', Auth::id());
            Session::put('payment_user_email', Auth::user()->email);
        }
        
        Log::info('Order created and session stored:', [
            'order_id' => $orderId,
            'transaction_id' => $tran_id,
            'user_id' => $request->user_id,
            'session_id' => session()->getId()
        ]);
        
        // Initialize SSLCommerz
        $sslc = new SslCommerzNotification();
        
        // Use 'hosted' mode - this will redirect to SSLCommerz gateway
        $payment_options = $sslc->makePayment($post_data, 'hosted');
        
        if (!is_array($payment_options)) {
            Log::error('SSLCommerz payment initiation failed: ' . print_r($payment_options, true));
            
            // Clean up failed order
            DB::table('orders')->where('id', $orderId)->delete();
            
            throw new \Exception('Payment gateway error. Please try again.');
        }
        
        // SSLCommerz will handle the redirect
        // The makePayment method should return an array with redirect URL
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Log validation errors
        Log::error('SSLCommerz Validation Error: ' . json_encode($e->errors()));
        Log::error('Request Data: ' . json_encode($request->all()));
        
        return redirect()->back()->withErrors([
            'error' => 'Please fill all required fields correctly: ' . implode(', ', array_keys($e->errors()))
        ]);
    } catch (\Exception $e) {
        Log::error('SSLCommerz initiation error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        
        // Clean up failed order
        if (isset($orderId)) {
            DB::table('orders')->where('id', $orderId)->delete();
        }
        
        return redirect()->back()->withErrors(['error' => 'Payment initiation failed: ' . $e->getMessage()]);
    }
}

private function createOrderDetails($orderId, $cartData, $request)
{
    try {
        // Get user_id from request
        $userId = $request->user_id ?? $request->input('user_id') ?? null;
        
        Log::info('Creating order details with user_id: ' . $userId);
        
        # Convert cart data to proper format if it's an array
        if (is_array($cartData)) {
            // Convert array to collection-like object
            $cartCollection = collect($cartData)->map(function ($item) {
                // Convert array to object
                return (object) $item;
            });
        } else {
            $cartCollection = $cartData;
        }
        
        # Group cart items by store/weight
        $groupedItems = [];
        
        // Handle both array and collection
        if (is_array($cartCollection)) {
            foreach ($cartCollection as $item) {
                $storeId = $item->weight ?? 0;
                if (!isset($groupedItems[$storeId])) {
                    $groupedItems[$storeId] = [];
                }
                $groupedItems[$storeId][] = $item;
            }
        } else {
            // Original logic for collections
            foreach ($cartCollection as $item) {
                $storeId = $item->weight ?? 0;
                if (!isset($groupedItems[$storeId])) {
                    $groupedItems[$storeId] = [];
                }
                $groupedItems[$storeId][] = $item;
            }
        }
        
        # Process each store's items
        foreach ($groupedItems as $storeId => $items) {
            # Calculate totals for this store
            $sellprice = 0;
            $buy = 0;
            $bonus = 0;
            
            foreach ($items as $item) {
                // Handle both object and array access
                $productId = is_object($item) ? $item->id : $item['id'];
                $product = Product::find($productId);
                
                if ($product) {
                    $quantity = is_object($item) ? $item->qty : $item['qty'];
                    $price = is_object($item) ? $item->price : $item['price'];
                    
                    // Use selling_price from options if available for correct profit calculation
                    $itemOptions = is_object($item) ? ($item->options ?? null) : ($item['options'] ?? null);
                    if (is_string($itemOptions)) {
                        $itemOptions = json_decode($itemOptions, true);
                    } elseif (is_object($itemOptions)) {
                        $itemOptions = (array) $itemOptions;
                    }
                    $itemSellingPrice = !empty($itemOptions['selling_price']) ? (float) $itemOptions['selling_price'] : (float) $price;
                    
                    $sellprice += $itemSellingPrice * $quantity;
                    $buy += ($product->ProductResellerPrice ?? 0) * $quantity;
                    $bonus += $product->reseller_bonus ?? 0;
                }
            }
            
            $shopamount = $sellprice;
            $profit = $shopamount - $buy;
            
            # Get admin/executive for this store
            $admin = Admin::where('id', $storeId)
                ->where('status', 'Active')
                ->first();
            
            if (!$admin) {
                # Try to get a random executive
                $admin = Admin::whereHas('roles', function ($q) {
                    $q->where('name', 'Executive');
                })
                ->where('add_by', $storeId)
                ->where('status', 'Active')
                ->inRandomOrder()
                ->first();
            }
            
            # Create main order for this store - USE THE USER_ID FROM REQUEST
            $storeOrder = new Order();
            $storeOrder->profit = $profit;
            $storeOrder->order_bonus = $bonus;
            $storeOrder->user_id = $userId; // Use the user_id we got
            $storeOrder->courier_id = 26; // Default courier
            $storeOrder->store_id = $storeId;
            $storeOrder->invoiceID = $this->uniqueID();
            $storeOrder->subTotal = $shopamount;
            $storeOrder->deliveryCharge = $request->delivery_charge ?? $request->deliveryCharge ?? 0;
            $storeOrder->paymentAmount = $request->delivery_charge ?? $request->deliveryCharge ?? 0;
            $storeOrder->payment_type_id = 6;
            $storeOrder->transaction_id = 'STORE_' . $orderId . '_' . $storeId;
            $storeOrder->orderDate = date('Y-m-d');
            $storeOrder->admin_id = $admin->id ?? $storeId;
            $storeOrder->save();
            
            Log::info('Store order created with user_id: ' . $userId);
            
            # Create customer record
            $customer = new Customer();
            $customer->order_id = $storeOrder->id;
            $customer->customerName = $request->customer_name ?? $request->customerName;
            $customer->customerPhone = $request->customer_phone ?? $request->customerPhone;
            $customer->customerAddress = $request->customer_address ?? $request->customerAddress;
            $customer->save();
            
            # Create order products
            foreach ($items as $item) {
                $orderProduct = new Orderproduct();
                $orderProduct->order_id = $storeOrder->id;
                $orderProduct->product_id = is_object($item) ? $item->id : $item['id'];
                $orderProduct->productCode = is_object($item) ? 
                    ($item->options->code ?? '') : 
                    ($item['options']['code'] ?? '');
                $orderProduct->color = is_object($item) ? 
                    ($item->options->color ?? null) : 
                    ($item['options']['color'] ?? null);
                $orderProduct->size = is_object($item) ? 
                    ($item->options->size ?? null) : 
                    ($item['options']['size'] ?? null);
                $orderProduct->productName = is_object($item) ? $item->name : $item['name'];
                $orderProduct->quantity = is_object($item) ? $item->qty : $item['qty'];
                $orderProduct->productPrice = is_object($item) ? $item->price : $item['price'];
                $orderProduct->save();
                
                // Reduce product stock
                $orderedQty = is_object($item) ? $item->qty : $item['qty'];
                $productId = is_object($item) ? $item->id : $item['id'];
                DB::table('products')
                    ->where('id', $productId)
                    ->where('qty', '>', 0)
                    ->decrement('qty', (int) $orderedQty);
                
                # Create notification
                $notification = new Comment();
                $notification->order_id = $storeOrder->id;
                $notification->comment = $storeOrder->invoiceID . ' Order Has Been Created for ' . ($admin->name ?? 'Store');
                $notification->admin_id = $storeOrder->admin_id;
                $notification->save();
            }
        }
        
        return true;
        
    } catch (\Exception $e) {
        Log::error('Order details creation failed: ' . $e->getMessage());
        Log::error('Error trace: ' . $e->getTraceAsString());
        Log::error('Cart data type: ' . gettype($cartData));
        
        if (is_array($cartData)) {
            Log::error('Cart data sample: ' . json_encode(array_slice($cartData, 0, 1)));
        }
        
        throw $e;
    }
}

public function success(Request $request)
{
    
        // **CHECK IF THIS IS A PACKAGE PAYMENT**
    $tran_id = $request->input('tran_id');
    
    // Package payments have PKG_ prefix in transaction ID
    if (strpos($tran_id, 'PKG_') === 0) {
        Log::info('Detected package payment, redirecting to packagePaymentSuccess');
        return $this->packagePaymentSuccess($request);
    }

        
    try {
        Log::info('SSLCommerz Success Callback - Starting:', $request->all());
        
        // IMPORTANT: Start session immediately
        if (!session()->isStarted()) {
            session()->start();
        }
        
        // Get user_id from SSLCommerz callback (value_a)
        $userIdFromPayment = $request->input('value_a');
        Log::info('User ID from payment callback: ' . $userIdFromPayment);
        
        // Also try to get from value_d JSON
        $valueD = $request->input('value_d');
        $valueDData = [];
        if ($valueD && $valueD !== '{') {
            try {
                $valueDData = json_decode($valueD, true);
                if (isset($valueDData['user_id']) && $valueDData['user_id'] != '0') {
                    $userIdFromPayment = $valueDData['user_id'];
                    Log::info('User ID from value_d JSON: ' . $userIdFromPayment);
                }
            } catch (\Exception $e) {
                Log::error('Error parsing value_d: ' . $e->getMessage());
            }
        }
        
        $tran_id = $request->input('tran_id');
        
        if (!$tran_id) {
            Log::error('No transaction ID in success callback');
            return redirect('/checkout')->withErrors([
                'error' => 'Invalid transaction ID'
            ]);
        }
        
        // Get session data
        $sessionOrderId = Session::get('sslcommerz_order_id');
        $cartData = Session::get('cart_data');
        $orderData = Session::get('order_data');
        
        Log::info('Session Data Retrieved:', [
            'sessionOrderId' => $sessionOrderId,
            'hasCartData' => !empty($cartData),
            'hasOrderData' => !empty($orderData),
            'user_id_in_session' => Session::get('payment_user_id'),
            'user_id_from_callback' => $userIdFromPayment,
            'is_logged_in' => Auth::check() ? 'Yes' : 'No'
        ]);
        
        // If session data is missing, try to get from database
        if (!$sessionOrderId || !$cartData) {
            Log::info('Missing session data, checking database for transaction: ' . $tran_id);
            
            $order = DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->first();
                
            if ($order) {
                $sessionOrderId = $order->id;
                $cartData = json_decode($order->cart, true);
                $orderData = json_decode($order->data, true);
                
                // Restore to session
                Session::put('sslcommerz_order_id', $sessionOrderId);
                Session::put('cart_data', $cartData);
                Session::put('order_data', $orderData);
                
                Log::info('Found order in database:', ['order_id' => $sessionOrderId]);
                
                // If user is not logged in but we have user_id from order, try to restore
                if (!$userIdFromPayment || $userIdFromPayment == '0') {
                    // Get user_id from order
                    $userIdFromPayment = $order->user_id;
                    Log::info('User ID from order table: ' . $userIdFromPayment);
                }
            } else {
                Log::error('Order not found in database for transaction: ' . $tran_id);
                return redirect('/checkout')->withErrors([
                    'error' => 'Order not found. Please contact support.'
                ]);
            }
        }
        
        // Validate payment - SIMPLIFIED like payViaAjax
        $status = $request->input('status');
        $val_id = $request->input('val_id');
        $paid_amount = $request->input('amount', 0);
        
        Log::info('Payment validation parameters:', [
            'status' => $status,
            'val_id' => $val_id,
            'paid_amount' => $paid_amount
        ]);
        
        // Simple validation - if SSLCommerz says it's VALID, accept it
        if ($status == 'VALID' && $val_id) {
            Log::info('Payment validated by SSLCommerz status: VALID');
            $validation = true;
        } else {
            Log::error('Payment validation failed - invalid status or val_id');
            $validation = false;
        }
        
        if (!$validation) {
            Log::error('Payment validation failed for transaction: ' . $tran_id);
            
            // Update order status to Failed
            $currentData = DB::table('orders')
                ->where('id', $sessionOrderId)
                ->value('data');
            
            $dataArray = $currentData ? json_decode($currentData, true) : [];
            $dataArray['payment_status'] = 'Failed';
            $dataArray['validation_failed'] = true;
            $dataArray['paid_amount'] = $paid_amount;
            
            DB::table('orders')
                ->where('id', $sessionOrderId)
                ->update([
                    'status' => 'Failed',
                    'data' => json_encode($dataArray),
                    'updated_at' => now(),
                ]);
            
            // Clear only payment-related session
            Session::forget(['sslcommerz_tran_id', 'sslcommerz_order_id', 'cart_data', 'order_data']);
            
            return redirect('/checkout')->withErrors([
                'error' => 'Payment validation failed. Please try again.'
            ]);
        }
        
        Log::info('Payment validation successful. Processing order...');
        
        // FIRST: Read the original order data (has customer info from initiatePayment)
        $originalData = DB::table('orders')
            ->where('id', $sessionOrderId)
            ->value('data');
        $originalDataArray = $originalData ? json_decode($originalData, true) : [];
        
        // Extract customer info before we modify the data column
        $savedCustomerName = $originalDataArray['customer_name'] ?? ($originalDataArray['customerName'] ?? null);
        $savedCustomerPhone = $originalDataArray['customer_phone'] ?? ($originalDataArray['customerPhone'] ?? null);
        $savedCustomerAddress = $originalDataArray['customer_address'] ?? ($originalDataArray['customerAddress'] ?? null);
        
        // Fallback: try to get customer info from value_d (SSLCommerz callback data)
        if ((!$savedCustomerName || !$savedCustomerPhone) && !empty($valueDData)) {
            $savedCustomerName = $savedCustomerName ?: ($valueDData['customer_name'] ?? null);
            $savedCustomerPhone = $savedCustomerPhone ?: ($valueDData['customer_phone'] ?? null);
            $savedCustomerAddress = $savedCustomerAddress ?: ($valueDData['customer_address'] ?? null);
        }
        
        Log::info('Customer data from saved order:', [
            'name' => $savedCustomerName,
            'phone' => $savedCustomerPhone,
            'address' => $savedCustomerAddress,
        ]);
        
        // Update the main order with user_id if missing
        if ($userIdFromPayment && $userIdFromPayment != '0') {
            DB::table('orders')
                ->where('id', $sessionOrderId)
                ->update([
                    'user_id' => $userIdFromPayment,
                    'updated_at' => now(),
                ]);
            Log::info('Updated main order with user_id: ' . $userIdFromPayment);
        }
        
        // Payment successful - Update order status and payment info
        $originalDataArray['payment_status'] = 'Paid';
        $originalDataArray['payment_date'] = now()->toDateTimeString();
        $originalDataArray['paid_amount'] = $paid_amount;
        $originalDataArray['user_id'] = $userIdFromPayment;
        // Don't store entire sslcommerz response to avoid bloating and corruption
        $originalDataArray['sslcommerz_tran_id'] = $tran_id;
        $originalDataArray['sslcommerz_val_id'] = $val_id;
        
        DB::table('orders')
            ->where('id', $sessionOrderId)
            ->update([
                'status' => 'Pending',
                'data' => json_encode($originalDataArray),
                'updated_at' => now(),
            ]);
        
        Log::info('Main order updated. Creating detailed order records...');
        
        // Prepare order data for createOrderDetails
        $orderRequestData = [];
        if ($orderData && is_array($orderData)) {
            $orderRequestData = $orderData;
        } else {
            $orderRequestData = [
                'user_id' => $userIdFromPayment,
                'customer_name' => $savedCustomerName ?? 'Customer',
                'customer_phone' => $savedCustomerPhone ?? '',
                'customer_address' => $savedCustomerAddress ?? '',
                'delivery_charge' => $originalDataArray['delivery_charge_per_shop'] ?? ($request->input('value_c') ?? 0),
                'deliveryCharge' => $originalDataArray['delivery_charge_per_shop'] ?? ($request->input('value_c') ?? 0),
                'customer_note' => $originalDataArray['customer_note'] ?? '',
                'sub_total' => $originalDataArray['cart_subtotal'] ?? 0,
                'subTotal' => $originalDataArray['cart_subtotal'] ?? 0,
            ];
        }
        
        // Add user_id to order data
        $orderRequestData['user_id'] = $userIdFromPayment;
        
        // Create customer record for the main order
        try {
            $custName  = $orderRequestData['customer_name']  ?? 'Customer';
            $custPhone = $orderRequestData['customer_phone'] ?? '';
            $custAddr  = $orderRequestData['customer_address'] ?? '';

            $mainCustomer = new Customer();
            $mainCustomer->order_id = $sessionOrderId;
            $mainCustomer->customerName = $custName;
            $mainCustomer->customerPhone = $custPhone;
            $mainCustomer->customerAddress = $custAddr;
            $mainCustomer->save();

            Log::info('Main order customer created', [
                'order_id' => $sessionOrderId,
                'name'     => $custName,
                'phone'    => $custPhone,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating main customer: ' . $e->getMessage());
        }

        // Create order products for the MAIN order directly from cart
        try {
            $mainCartData = $cartData;
            if (!$mainCartData) {
                $rawCart = DB::table('orders')->where('id', $sessionOrderId)->value('cart');
                $mainCartData = $rawCart ? json_decode($rawCart, true) : [];
            }
            if (is_array($mainCartData)) {
                foreach ($mainCartData as $key => $value) {
                    // Handle nested structure {"1.00": [items]} or flat [items]
                    $items = (is_array($value) && !isset($value['id'])) ? $value : [$value];
                    foreach ($items as $item) {
                        if (!is_array($item)) continue;
                        // Cart stores 'id' as cart row ID, 'product_id' as the actual product ID
                        $productId = $item['product_id'] ?? $item['id'] ?? null;
                        if (!$productId) continue;
                        $op = new Orderproduct();
                        $op->order_id = $sessionOrderId;
                        $op->product_id = $productId;
                        $op->productName = $item['name'] ?? 'Product';
                        $op->quantity = $item['qty'] ?? 1;
                        $op->productPrice = $item['price'] ?? 0;
                        $op->productCode = $item['options']['code'] ?? '';
                        $op->color = $item['options']['color'] ?? null;
                        $op->size = $item['options']['size'] ?? null;
                        $op->save();
                        Log::info('Main order product created: ' . $op->productName . ' (product_id: ' . $productId . ')');
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error creating main order products: ' . $e->getMessage());
        }

        // Create detailed order records (per-store sub-orders)
        try {
            $this->createOrderDetails($sessionOrderId, $cartData, new Request($orderRequestData));
            Log::info('Order details created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating order details: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            // Continue anyway - the main order is created
        }
        
        // Clear cart
        Cart::destroy();
        Log::info('Cart cleared.');
        
        // Store order ID in session for the order-received page
        Session::put('last_successful_order_id', $sessionOrderId);
        Session::put('payment_completed_at', now()->toDateTimeString());
        
        // Also store user_id for the payment page
        if ($userIdFromPayment && $userIdFromPayment != '0') {
            Session::put('payment_user_id', $userIdFromPayment);
        }
        
        // Clear ONLY SSLCommerz specific session data
        Session::forget(['sslcommerz_tran_id', 'sslcommerz_order_id', 'cart_data', 'order_data']);
        
        Log::info('Redirecting to order-received page...');
        
        return redirect($this->frontendUrl('/order-received', [
            'order_id' => $sessionOrderId,
        ]));
        
    } catch (\Exception $e) {
        Log::error('Payment success error: ' . $e->getMessage());
        Log::error('Error trace: ' . $e->getTraceAsString());
        
        return redirect('/checkout')->withErrors([
            'error' => 'An error occurred while processing your payment. Please contact support.'
        ]);
    }
}

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');

        // Package payments are handled in dedicated package callbacks.
        if ($tran_id && strpos($tran_id, 'PKG_') === 0) {
            Log::info('Detected package payment fail callback');
            return $this->packagePaymentFail($request);
        }
        
        if ($tran_id) {
            # Update order status to Failed
            DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->update([
                    'status' => 'Failed',
                    'payment_status' => 'Failed',
                    'updated_at' => now(),
                ]);
        }
        
        # Clear session
        Session::forget(['sslcommerz_tran_id', 'sslcommerz_order_id', 'cart_data', 'order_data']);
        
        return redirect()->away($this->frontendUrl('/checkout', [
            'payment' => 'failed',
        ]));
    }
    
    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');

        // Package payments are handled in dedicated package callbacks.
        if ($tran_id && strpos($tran_id, 'PKG_') === 0) {
            Log::info('Detected package payment cancel callback');
            return $this->packagePaymentCancel($request);
        }
        
        if ($tran_id) {
            # Update order status to Canceled
            DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->update([
                    'status' => 'Canceled',
                    'updated_at' => now(),
                ]);
        }
        
        # Clear session
        Session::forget(['sslcommerz_tran_id', 'sslcommerz_order_id', 'cart_data', 'order_data']);
        
        return redirect()->away($this->frontendUrl('/checkout', [
            'payment' => 'canceled',
        ]));
    }
    
    public function ipn(Request $request)
    {
        $tran_id = $request->input('tran_id');
        if ($tran_id && strpos($tran_id, 'PKG_') === 0) {
            Log::info('Detected package payment IPN callback');
            return $this->packagePaymentIPN($request);
        }

        # Instant Payment Notification handler
        if ($tran_id) {
            $order_details = DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'status', 'amount', 'currency')
                ->first();
            
            if ($order_details && $order_details->status == 'Pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->amount, $order_details->currency);
                
                if ($validation) {
                    # Update order status
                    DB::table('orders')
                        ->where('transaction_id', $tran_id)
                        ->update([
                            'status' => 'Processing',
                            'payment_status' => 'Paid',
                            'updated_at' => now(),
                        ]);
                    
                    echo "Transaction validated and updated via IPN";
                } else {
                    echo "IPN validation failed";
                }
            } else {
                echo "Order already processed or invalid";
            }
        } else {
            echo "Invalid IPN data";
        }
    }
    
    public function uniqueID()
    {
        $lastOrder = Order::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }
        return 'SS00' . $orderID;
    }
}
