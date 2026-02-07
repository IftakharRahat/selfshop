<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Resellerinvoice;
use App\Models\User;
use App\Models\Message;
use App\Models\Package;

class SslCommerzPackageController extends Controller
{
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
public function packagePaymentSuccess(Request $request)
{
    try {
        Log::info('SSLCommerz Package Payment Success Callback:', $request->all());
        
        $tran_id = $request->input('tran_id');
        $status = $request->input('status');
        $val_id = $request->input('val_id');
        
        if (!$tran_id) {
            Log::error('No transaction ID in package success callback');
            return redirect('/our-packages')->withErrors(['error' => 'Invalid transaction ID']);
        }
        
        // Get session data
        $sessionData = Session::get('sslcommerz_package_payment');
        
        // Validate payment with SSLCommerz
        $sslc = new SslCommerzNotification();
        $validation = $sslc->orderValidate($request->all(), $tran_id, 0, 'BDT');
        
        if (!$validation && $status != 'VALID') {
            Log::error('Package payment validation failed for transaction: ' . $tran_id);
            
            // Update invoice status to Failed
            if ($sessionData) {
                $invoice = \App\Models\Resellerinvoice::find($sessionData['invoice_id']);
                if ($invoice) {
                    $invoice->status = 'Failed';
                    $invoice->save();
                }
            }
            
            Session::forget('sslcommerz_package_payment');
            
            return redirect('/our-packages')->withErrors([
                'error' => 'Payment validation failed. Please try again.'
            ]);
        }
        
        Log::info('Package payment validated successfully');
        
        // Process successful payment
        if ($sessionData) {
            $invoice = \App\Models\Resellerinvoice::find($sessionData['invoice_id']);
            $user = \App\Models\User::find($sessionData['user_id']);
            $package = \App\Models\Package::find($sessionData['package_id']);
            
            if ($invoice && $user && $package) {
                // Update user status and activate account
                if ($invoice->status != 'Paid') {
                    // Give referral bonus
                    $referuser = \App\Models\User::where('my_referral_code', $user->refer_by)->first();
                    $refbonus = 200; // Adjust amount as needed
                    
                    if ($referuser) {
                        $referuser->referal_bonus += $refbonus;
                        $referuser->account_balance += $refbonus;
                        $referuser->save();
                        
                        // Create bonus message
                        $message = new \App\Models\Message();
                        $message->user_id = $referuser->id;
                        $message->message_for = 'Referral Bonus';
                        $message->message = 'You Get ' . $refbonus . ' TK As Your Referral Bonus';
                        $message->amount = $refbonus;
                        $message->date = date('Y-m-d');
                        $message->save();
                    }
                    
                    // Activate user account
                    $user->status = 'Active';
                    $user->membership_status = 'Paid';
                    $user->active_date = date('Y-m-d');
                    $user->p_system = 'Getway';
                    $user->save();
                }
                
                // Update invoice
                $invoice->paymentDate = date('Y-m-d');
                $invoice->paid_amount = $sessionData['amount'];
                $invoice->payment_type = 'SSLCommerz';
                $invoice->status = 'Paid';
                $invoice->save();
                
                Log::info('Package payment processed successfully:', [
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            }
        }
        
        // Clear session
        Session::forget('sslcommerz_package_payment');
        
        // Redirect to success page
        return redirect('/user/dashboard')->with([
            'success' => 'Package payment completed successfully! Your account is now active.',
            'invoice_id' => $invoice->invoiceID ?? ''
        ]);
        
    } catch (\Exception $e) {
        Log::error('Package payment success error: ' . $e->getMessage());
        Log::error('Error trace: ' . $e->getTraceAsString());
        
        return redirect('/our-packages')->withErrors([
            'error' => 'An error occurred while processing your payment. Please contact support.'
        ]);
    }
}

/**
 * Handle SSLCommerz fail callback for package payment
 */
public function packagePaymentFail(Request $request)
{
    Log::info('SSLCommerz Package Payment Fail Callback:', $request->all());
    
    $tran_id = $request->input('tran_id');
    
    if ($tran_id) {
        // Update invoice status to Failed
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
        if ($invoice) {
            $invoice->status = 'Failed';
            $invoice->save();
            Log::info('Invoice marked as failed:', ['invoice_id' => $invoice->id]);
        }
    }
    
    Session::forget('sslcommerz_package_payment');
    
    return redirect('/our-packages')->withErrors([
        'error' => 'Package payment failed. Please try again.'
    ]);
}

/**
 * Handle SSLCommerz cancel callback for package payment
 */
public function packagePaymentCancel(Request $request)
{
    Log::info('SSLCommerz Package Payment Cancel Callback:', $request->all());
    
    $tran_id = $request->input('tran_id');
    
    if ($tran_id) {
        // Update invoice status to Canceled
        $invoice = \App\Models\Resellerinvoice::where('payment_id', $tran_id)->first();
        if ($invoice) {
            $invoice->status = 'Canceled';
            $invoice->save();
            Log::info('Invoice marked as canceled:', ['invoice_id' => $invoice->id]);
        }
    }
    
    Session::forget('sslcommerz_package_payment');
    
    return redirect('/our-packages')->with('warning', 'Package payment was canceled.');
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
                $invoice->save();
                
                // Activate user
                $user = \App\Models\User::find($invoice->user_id);
                if ($user) {
                    $user->status = 'Active';
                    $user->membership_status = 'Paid';
                    $user->active_date = date('Y-m-d');
                    $user->p_system = 'Getway';
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
}