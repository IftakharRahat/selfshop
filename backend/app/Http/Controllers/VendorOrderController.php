<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Services\SteadfastOrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendorOrderController extends Controller
{
    public function __construct(
        protected SteadfastOrderStatusService $steadfastService
    ) {}

    private function getVendor()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }

        return $user->vendor;
    }

    private function vendorItemsForOrder(Order $order, int $vendorId)
    {
        return $order->orderproducts->filter(function ($op) use ($vendorId) {
            return $op->product && (int) $op->product->vendor_id === $vendorId;
        });
    }

    private function statusMeta(Order $order): array
    {
        return $this->steadfastService->getStatusPayload($order);
    }

    private function touchStatusSync(Order $order, bool $force = false): array
    {
        return $this->steadfastService->syncOrderStatus($order, $force);
    }

    private function canVendorMutateWholeOrder(Order $order, int $vendorId): bool
    {
        $totalItems = $order->orderproducts->count();
        if ($totalItems === 0) {
            return false;
        }

        $vendorItems = $this->vendorItemsForOrder($order, $vendorId)->count();

        return $vendorItems === $totalItems;
    }

    private function createCustomerComment(Order $order, string $message): void
    {
        if (!$order->user_id) {
            return;
        }

        $comment = new Comment();
        $comment->order_id = $order->id;
        $comment->user_id = $order->user_id;
        $comment->status = 1;
        $comment->comment = $message;
        $comment->save();
    }

    /**
     * Sync Carry Bee order status from their API.
     * Non-blocking — if the call fails, the existing status is kept.
     */
    private function syncCarryBeeStatus(Order $order): void
    {
        if (empty($order->carrybee_parcel_id) && empty($order->carrybee_tracking_code)) {
            return;
        }

        // Don't re-sync terminal statuses
        $terminal = ['delivered', 'returned', 'returned-to-merchant', 'cancelled'];
        if (in_array(strtolower($order->carrybee_status ?? ''), $terminal, true)) {
            // Even if carrybee_status is terminal, ensure the main order status is synced
            $this->mapCarryBeeToOrderStatus($order);
            return;
        }

        try {
            $carryBee = app(\App\Services\CarryBeeService::class);

            // Try primary ID first, fall back to tracking code
            $lookupId = $order->carrybee_parcel_id ?: $order->carrybee_tracking_code;
            $details = $carryBee->getOrderDetails((string) $lookupId);

            // Try multiple response field paths for status
            $newStatus = $details['data']['transfer_status']
                ?? $details['data']['order_status']
                ?? $details['data']['status']
                ?? $details['transfer_status']
                ?? $details['status']
                ?? null;

            if ($newStatus) {
                $order->carrybee_status = (string) $newStatus;

                // Map Carrybee status to main order status
                $this->mapCarryBeeToOrderStatus($order);

                $order->save();
            }
        } catch (\Throwable $e) {
            \Log::warning('CarryBee status sync failed (vendor)', [
                'order_id' => $order->id,
                'lookup_id' => $lookupId ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map Carrybee courier status to the main order status field.
     * Updates orders.status when courier reports a terminal state.
     */
    private function mapCarryBeeToOrderStatus(Order $order): void
    {
        $cbStatus = strtolower($order->carrybee_status ?? '');
        if ($cbStatus === '') {
            return;
        }

        $currentStatus = strtolower($order->status ?? '');

        // Don't downgrade from Delivered/Return/Paid (already terminal in our system)
        if (in_array($currentStatus, ['delivered', 'return', 'paid'], true)) {
            return;
        }

        if (str_contains($cbStatus, 'deliver')) {
            $order->status = 'Delivered';
            $order->deliveryDate = $order->deliveryDate ?? now()->format('Y-m-d');
            $order->save();

            // Credit reseller balance, record income, mark vendor earnings
            try {
                app(\App\Services\OrderDeliveryService::class)->markDelivered($order);
            } catch (\Throwable $e) {
                \Log::warning('CarryBee delivery service failed (vendor)', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (str_contains($cbStatus, 'return') || str_contains($cbStatus, 'cancelled')) {
            $order->status = 'Return';
            $order->completeDate = $order->completeDate ?? now()->format('Y-m-d');

            // Restore stock on return
            try {
                app(\App\Services\StockService::class)->restoreForOrder($order->id);
            } catch (\Throwable $e) {
                \Log::warning('Stock restore on CarryBee return failed (vendor)', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Orders that contain at least one product from this vendor.
     * Returns order list with vendor item count and vendor subtotal per order.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $query = Order::query()
            ->whereHas('orderproducts.product', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->with([
                'customer',
                'orderproducts' => function ($q) use ($vendor) {
                    $q->whereHas('product', fn($p) => $p->where('vendor_id', $vendor->id))
                        ->with('product:id,ProductName,ProductSku,vendor_id');
                },
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoiceID', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', fn($c) => $c->where('customerName', 'like', '%' . $search . '%')
                        ->orWhere('customerPhone', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment')) {
            $query->where('Payment', $request->payment);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 5), 50);
        $orders = $query->paginate($perPage);

        $items = $orders->getCollection()->map(function ($order) use ($vendor) {
            $vendorItems = $order->orderproducts->filter(fn($op) => $op->product && (int) $op->product->vendor_id === (int) $vendor->id);
            $vendorSubtotal = $vendorItems->sum(fn($op) => (float) $op->productPrice * (int) $op->quantity);
            $orderDate = $order->orderDate;
            if ($orderDate instanceof \DateTimeInterface) {
                $orderDate = $orderDate->format('Y-m-d');
            }

            $meta = $this->statusMeta($order);

            return [
                'id' => $order->id,
                'invoiceID' => $order->invoiceID,
                'orderDate' => $orderDate,
                'status' => $order->status,
                'display_status' => $meta['customer_status'],
                'customer_status' => $meta['customer_status'],
                'steadfast_status' => $meta['steadfast_status'],
                'steadfast_last_synced_at' => $meta['steadfast_last_synced_at'],
                'warehouse_sent_at' => $meta['warehouse_sent_at'],
                'Payment' => $order->Payment,
                'paymentAmount' => $order->paymentAmount,
                'subTotal' => $order->subTotal,

                'vendor_item_count' => $vendorItems->count(),
                'vendor_subtotal' => round($vendorSubtotal, 2),
            ];
        });
        $orders->setCollection($items);

        return response()->json([
            'status' => true,
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ],
        ]);
    }

    /**
     * Single order detail: only line items that belong to this vendor, plus order info.
     */
    public function show($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $order = Order::with([
            'customer',
            'users:id,name,shop_name',
            'orderproducts' => function ($q) use ($vendor) {
                $q->whereHas('product', fn($p) => $p->where('vendor_id', $vendor->id))
                    ->with('product:id,ProductName,ProductSku,ViewProductImage,vendor_id');
            },
        ])->findOrFail($id);

        $vendorOrderProducts = $order->orderproducts->filter(fn($op) => $op->product && (int) $op->product->vendor_id === (int) $vendor->id);
        if ($vendorOrderProducts->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Order not found or contains no your products'], 404);
        }

        // Pull fresh courier status when opening detail page.
        $meta = $this->touchStatusSync($order, false);

        // Sync Carry Bee status if order has a carrybee parcel
        $this->syncCarryBeeStatus($order);

        $vendorSubtotal = $vendorOrderProducts->sum(fn($op) => (float) $op->productPrice * (int) $op->quantity);
        $customer = $order->customer;
        $reseller = $order->users; // reseller who placed the order

        return response()->json([
            'status' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'invoiceID' => $order->invoiceID,
                    'orderDate' => $order->orderDate instanceof \DateTimeInterface ? $order->orderDate->format('Y-m-d') : $order->orderDate,
                    'deliveryDate' => $order->deliveryDate instanceof \DateTimeInterface ? $order->deliveryDate->format('Y-m-d') : $order->deliveryDate,
                    'status' => $order->status,
                    'display_status' => $meta['customer_status'],
                    'customer_status' => $meta['customer_status'],
                    'steadfast_status' => $meta['steadfast_status'],
                    'steadfast_last_synced_at' => $meta['steadfast_last_synced_at'],
                    'warehouse_sent_at' => $meta['warehouse_sent_at'],
                    'Payment' => $order->Payment,
                    'paymentAmount' => $order->paymentAmount,
                    'subTotal' => $order->subTotal,
                    'deliveryCharge' => $order->deliveryCharge,
                    'discountCharge' => $order->discountCharge,
                    'customerNote' => $order->customerNote,
                    'tracking_number' => $order->tracking_number,
                    'trackingLink' => $order->trackingLink,
                    'carrybee_parcel_id' => $order->carrybee_parcel_id,
                    'carrybee_tracking_code' => $order->carrybee_tracking_code,
                    'carrybee_status' => $order->carrybee_status,
                    'shipped_at' => $order->shipped_at?->toIso8601String(),
                ],

                'line_items' => $vendorOrderProducts->values()->map(fn($op) => [
                    'id' => $op->id,
                    'product_id' => $op->product_id,
                    'productName' => $op->productName,
                    'productCode' => $op->productCode,
                    'color' => $op->color,
                    'size' => $op->size,
                    'productPrice' => $op->productPrice,
                    'quantity' => $op->quantity,
                    'line_total' => (float) $op->productPrice * (int) $op->quantity,
                    'tracking_number' => $op->tracking_number,
                    'shipped_at' => $op->shipped_at?->toIso8601String(),
                    'fulfillment_status' => $op->fulfillment_status ?? 'pending',
                    'fulfillment_type' => $op->fulfillment_type,
                    'product' => $op->product ? [
                        'id' => $op->product->id,
                        'ProductName' => $op->product->ProductName,
                        'ViewProductImage' => $op->product->ViewProductImage,
                    ] : null,
                ]),
                'customer' => $customer ? [
                    'customerName' => $customer->customerName,
                    'customerPhone' => $customer->customerPhone
                        ? str_repeat('*', max(0, strlen($customer->customerPhone) - 4)) . substr($customer->customerPhone, -4)
                        : null,
                    'customerAddress' => $customer->customerAddress,
                ] : null,
                'reseller' => $reseller ? [
                    'shop_name' => $reseller->shop_name,
                    'name' => $reseller->name,
                ] : null,
                'vendor_subtotal' => round($vendorSubtotal, 2),
            ],
        ]);
    }

    /**
     * Vendor action: accept / cancel.
     * Accept -> order.status Confirmed
     * Cancel -> order.status Canceled
     * Customer-facing labels are returned as customer_status.
     */
    public function updateStatus(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:accept,cancel',
            'cancel_reason' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $order = Order::with(['orderproducts.product'])->findOrFail($id);
        $vendorItems = $this->vendorItemsForOrder($order, (int) $vendor->id);
        if ($vendorItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Order not found or contains no your products'], 404);
        }



        $action = $request->input('action');

        if ($action === 'accept') {
            if (!in_array((string) $order->status, ['Pending', 'Processing', 'Confirmed'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order cannot be accepted in current state: ' . $order->status,
                ], 409);
            }

            $order->status = 'Confirmed';
            $order->save();

            $this->createCustomerComment(
                $order,
                'Your order ' . $order->invoiceID . ' has been accepted by vendor.'
            );
        } else {
            if (in_array((string) $order->status, ['Delivered', 'Return'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Delivered/returned orders cannot be cancelled by vendor.',
                ], 409);
            }

            $order->status = 'Canceled';
            // Restore product stock on vendor cancellation
            app(\App\Services\StockService::class)->restoreForOrder($order->id);

            if ($request->filled('cancel_reason')) {
                $order->cancel_comment = (string) $request->cancel_reason;
            }
            $order->save();

            $this->createCustomerComment(
                $order,
                'Your order ' . $order->invoiceID . ' has been rejected by vendor.'
            );
        }

        $meta = $this->statusMeta($order);

        // Real-time push notification
        try {
            $pushService = app(\App\Services\PushNotificationService::class);
            $pushService->onVendorAction($order, $action);
        } catch (\Throwable $e) {
            \Log::warning('Push notification failed for vendor action', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => true,
            'message' => $action === 'accept' ? 'Order accepted' : 'Order cancelled',
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'customer_status' => $meta['customer_status'],
                'steadfast_status' => $meta['steadfast_status'],
            ],
        ]);
    }

    /**
     * Vendor action: send accepted order to warehouse via Steadfast create_order.
     */
    public function sendToWarehouse($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $order = Order::with(['customer', 'orderproducts.product'])->findOrFail($id);
        $vendorItems = $this->vendorItemsForOrder($order, (int) $vendor->id);
        if ($vendorItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Order not found or contains no your products'], 404);
        }



        if (!in_array((string) $order->status, ['Confirmed', 'Processing', 'Pending', 'Ontheway'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Order cannot be sent to warehouse in current state: ' . $order->status,
            ], 409);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->customerName || !$customer->customerAddress || !$customer->customerPhone) {
            return response()->json([
                'status' => false,
                'message' => 'Customer shipping info is incomplete for warehouse dispatch.',
            ], 422);
        }

        // ── Steadfast consignment (non-blocking) ──
        $steadfastOk = false;
        $steadfastMessage = null;
        try {
            $created = $this->steadfastService->createConsignment(
                $order,
                (string) $customer->customerName,
                (string) $customer->customerAddress,
                (string) $customer->customerPhone
            );

            if ($created['ok']) {
                $steadfastOk = true;
                if (!empty($created['tracking_code'])) {
                    $order->tracking_number = (string) $created['tracking_code'];
                    $order->trackingLink = 'https://steadfast.com.bd/t/' . $created['tracking_code'];
                }
                if (!empty($created['consignment_id'])) {
                    $order->steadfast_consignment_id = (string) $created['consignment_id'];
                }
                if (!empty($created['raw_status'])) {
                    $order->steadfast_status = (string) $created['raw_status'];
                }
                $order->steadfast_payload = json_encode($created['payload'] ?? []);
                $order->steadfast_last_synced_at = now();
            } else {
                $steadfastMessage = $created['message'] ?? 'Steadfast failed';
                \Log::warning('Steadfast create_order failed (non-blocking)', [
                    'order_id' => $order->id,
                    'message' => $steadfastMessage,
                ]);
            }
        } catch (\Throwable $e) {
            $steadfastMessage = $e->getMessage();
            \Log::warning('Steadfast create_order exception (non-blocking)', [
                'order_id' => $order->id,
                'error' => $steadfastMessage,
            ]);
        }

        $order->warehouse_sent_at = now();
        $order->shipped_at = $order->shipped_at ?? now();
        $order->status = 'Ontheway';
        $order->save();

        // ── Carry Bee order creation (non-blocking) ──
        // Auto-create a Carry Bee store if vendor doesn't have one yet
        $carrybeeResult = null;
        $carrybeeError = null;
        if (empty($vendor->carrybee_store_id)) {
            try {
                $carryBee = app(\App\Services\CarryBeeService::class);
                $storeResult = $carryBee->createStore([
                    'name' => (string) ($vendor->company_name ?: 'Store ' . $vendor->id),
                    'contact_person_name' => (string) ($vendor->contact_name ?: $vendor->company_name),
                    'contact_person_number' => (string) ($vendor->contact_phone ?: '01700000000'),
                    'address' => (string) ($vendor->pickup_address ?: $vendor->company_name . ', Dhaka'),
                    'city_id' => (int) ($vendor->pickup_city_id ?: 14),  // default: Dhaka
                    'zone_id' => (int) ($vendor->pickup_zone_id ?: 1),
                    'area_id' => (int) ($vendor->pickup_area_id ?: 1),
                ]);

                \Log::info('CarryBee auto store creation result', [
                    'vendor_id' => $vendor->id,
                    'result'    => $storeResult,
                ]);

                if (!empty($storeResult['data']['id'])) {
                    $vendor->carrybee_store_id = $storeResult['data']['id'];
                    $vendor->save();
                } else {
                    $carrybeeError = 'Store creation failed: ' . json_encode($storeResult);
                    \Log::warning('CarryBee store creation returned no ID', [
                        'vendor_id' => $vendor->id,
                        'response'  => $storeResult,
                    ]);
                }
            } catch (\Throwable $e) {
                $carrybeeError = 'Store creation exception: ' . $e->getMessage();
                \Log::warning('CarryBee auto store creation failed (non-blocking)', [
                    'vendor_id' => $vendor->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // If the vendor has a registered Carry Bee store, create a delivery order
        if (!empty($vendor->carrybee_store_id)) {
            try {
                $carryBee = app(\App\Services\CarryBeeService::class);

                // Ensure recipient_address is 10-200 chars (API requirement)
                $recipientAddress = (string) $customer->customerAddress;
                if (strlen($recipientAddress) < 10) {
                    $recipientAddress = str_pad($recipientAddress, 10, ', Dhaka');
                }

                // Calculate vendor-specific totals using RESELL price (what customer pays)
                $vendorResellTotal = $vendorItems->sum(function ($op) {
                    $resellPrice = (float) ($op->selling_price ?? $op->productPrice);
                    return $resellPrice * (int) $op->quantity;
                });
                $vendorItemCount = $vendorItems->sum(fn($op) => (int) $op->quantity);
                $productDescription = $vendorItems->map(fn($op) => $op->productName . ' x' . $op->quantity)->implode(', ');
                $deliveryCharge = (float) ($order->deliveryCharge ?? 0);

                $orderData = [
                    'store_id'            => (string) $vendor->carrybee_store_id,
                    'merchant_order_id'   => (string) $order->invoiceID,
                    'delivery_type'       => 1,  // 1=Normal, 2=Express
                    'product_type'        => 1,  // 1=Parcel, 2=Book, 3=Document
                    'recipient_name'      => (string) $customer->customerName,
                    'recipient_phone'     => (string) $customer->customerPhone,
                    'recipient_address'   => substr($recipientAddress, 0, 200),
                    'city_id'             => (int) ($order->city_id ?? 14),  // default Dhaka
                    'zone_id'             => (int) ($order->zone_id ?? 1),
                    'special_instruction' => (string) ($order->customerNote ?? ''),
                    'product_description'  => substr($productDescription, 0, 200),
                    'item_weight'         => 500,  // grams (default 500g)
                    'item_quantity'       => max(1, $vendorItemCount),
                    'collectable_amount'  => $order->advance_delivery
                        ? (int) round($vendorResellTotal)                              // Delivery already paid → collect resell price only
                        : (int) round($vendorResellTotal + $deliveryCharge),            // COD delivery → collect resell price + delivery charge
                ];

                // Only include area_id if available
                if (!empty($order->area_id)) {
                    $orderData['area_id'] = (int) $order->area_id;
                }

                \Log::info('CarryBee order creation request', [
                    'order_id' => $order->id,
                    'invoice'  => $order->invoiceID,
                    'data'     => $orderData,
                ]);

                $carrybeeResult = $carryBee->createOrder($orderData);

                \Log::info('CarryBee order creation response', [
                    'order_id' => $order->id,
                    'invoice'  => $order->invoiceID,
                    'result'   => $carrybeeResult,
                ]);

                // Store Carry Bee order details — response: data.order.consignment_id
                $cbOrder = $carrybeeResult['data']['order'] ?? null;
                if (is_array($cbOrder) && !empty($cbOrder['consignment_id'])) {
                    $order->carrybee_parcel_id = (string) $cbOrder['consignment_id'];
                    $order->carrybee_tracking_code = (string) $cbOrder['consignment_id'];
                    $order->carrybee_status = 'created';
                    $order->trackingLink = 'https://merchant.carrybee.com/order-track/' . $cbOrder['consignment_id'];
                    $order->save();
                } else {
                    // Try alternative response structures
                    $cbConsignment = $carrybeeResult['data']['consignment_id']
                        ?? $carrybeeResult['consignment_id']
                        ?? $carrybeeResult['data']['id']
                        ?? null;

                    if ($cbConsignment) {
                        $order->carrybee_parcel_id = (string) $cbConsignment;
                        $order->carrybee_tracking_code = (string) $cbConsignment;
                        $order->carrybee_status = 'created';
                        $order->trackingLink = 'https://merchant.carrybee.com/order-track/' . $cbConsignment;
                        $order->save();
                    } else {
                        $carrybeeError = 'Order created but no consignment_id in response: ' . json_encode($carrybeeResult);
                        \Log::warning('CarryBee order response missing consignment_id', [
                            'order_id' => $order->id,
                            'response' => $carrybeeResult,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $carrybeeError = 'Order creation exception: ' . $e->getMessage();
                \Log::warning('CarryBee order creation failed (non-blocking)', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        } elseif (!$carrybeeError) {
            $carrybeeError = 'Vendor has no carrybee_store_id and store creation was not attempted or failed.';
        }

        Orderproduct::whereIn('id', $vendorItems->pluck('id')->all())
            ->update([
                'fulfillment_status' => 'shipped',
                'shipped_at' => now(),
            ]);

        $this->createCustomerComment(
            $order,
            'Your order ' . $order->invoiceID . ' has been shipped to warehouse.'
        );

        $meta = $this->statusMeta($order);

        // Real-time push notification
        try {
            $pushService = app(\App\Services\PushNotificationService::class);
            $pushService->onWarehouseSent($order);
        } catch (\Throwable $e) {
            \Log::warning('Push notification failed for warehouse sent', ['error' => $e->getMessage()]);
        }

        $responseData = [
            'order_id' => $order->id,
            'status' => $order->status,
            'customer_status' => $meta['customer_status'],
            'steadfast_status' => $meta['steadfast_status'],
            'tracking_number' => $order->tracking_number,
            'tracking_link' => $order->trackingLink,
            'warehouse_sent_at' => $order->warehouse_sent_at?->toIso8601String(),
            'carrybee_parcel_id' => $order->carrybee_parcel_id,
            'carrybee_tracking_code' => $order->carrybee_tracking_code,
            'carrybee_status' => $order->carrybee_status,
        ];

        if ($carrybeeError) {
            $responseData['carrybee_warning'] = $carrybeeError;
        }

        return response()->json([
            'status' => true,
            'message' => 'Order sent to warehouse',
            'data' => $responseData,
        ]);
    }

    /**
     * Add or update tracking for vendor's items in an order (order-level or per line item).
     * Supports partial shipment: pass line_items with order_product_id and tracking_number.
     */
    public function addTracking(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $order = Order::with(['orderproducts' => function ($q) use ($vendor) {
            $q->whereHas('product', fn($p) => $p->where('vendor_id', $vendor->id));
        }])->findOrFail($id);

        $vendorOrderProducts = $order->orderproducts;
        if ($vendorOrderProducts->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Order not found or contains no your products'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tracking_number' => 'nullable|string|max:255',
            'line_items' => 'nullable|array',
            'line_items.*.order_product_id' => 'required_with:line_items|integer',
            'line_items.*.tracking_number' => 'nullable|string|max:255',
            'line_items.*.fulfillment_type' => 'nullable|in:standard,dropship',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $vendorOrderProductIds = $vendorOrderProducts->pluck('id')->toArray();

        try {
            // Per-line tracking
            if ($request->filled('line_items')) {
                foreach ($request->line_items as $row) {
                    $opId = (int) ($row['order_product_id'] ?? 0);
                    if (!in_array($opId, $vendorOrderProductIds, true)) {
                        continue;
                    }
                    $op = Orderproduct::find($opId);
                    if (!$op) {
                        continue;
                    }
                    $op->tracking_number = $row['tracking_number'] ?? null;
                    $op->fulfillment_status = ($row['tracking_number'] ?? '') !== '' ? 'shipped' : ($op->fulfillment_status ?? 'pending');
                    if (($row['tracking_number'] ?? '') !== '') {
                        $op->shipped_at = $op->shipped_at ?? now();
                    }
                    if (isset($row['fulfillment_type']) && in_array($row['fulfillment_type'], ['standard', 'dropship'], true)) {
                        $op->fulfillment_type = $row['fulfillment_type'];
                    }
                    $op->save();
                }
            }

            // Order-level tracking (applies to order record; used when single shipment for whole order)
            if ($request->filled('tracking_number')) {
                $order->tracking_number = $request->tracking_number;
                $order->trackingLink = 'https://steadfast.com.bd/t/' . $request->tracking_number;
                $order->shipped_at = $order->shipped_at ?? now();
                $order->warehouse_sent_at = $order->warehouse_sent_at ?? now();
                if (in_array((string) $order->status, ['Pending', 'Processing', 'Confirmed'], true)) {
                    $order->status = 'Ontheway';
                }
                $order->save();
            } else {
                // If we only updated line items, check if all vendor items are now shipped → update order
                $updated = Orderproduct::whereIn('id', $vendorOrderProductIds)->get();
                $allShipped = $updated->every(fn($op) => ($op->fulfillment_status ?? 'pending') === 'shipped');
                if ($allShipped && $updated->whereNotNull('tracking_number')->isNotEmpty()) {
                    $order->shipped_at = $order->shipped_at ?? now();
                    $order->warehouse_sent_at = $order->warehouse_sent_at ?? now();
                    if (in_array((string) $order->status, ['Processing', 'Pending', 'Confirmed'], true)) {
                        $order->status = 'Ontheway';
                    }
                    $order->save();
                }
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unknown column') || str_contains($msg, 'tracking_number') || str_contains($msg, 'shipped_at') || str_contains($msg, 'fulfillment')) {
                $msg = 'Database missing tracking columns. Run: php artisan migrate --path=database/migrations/2026_02_13_100001_add_tracking_to_orders_table.php and 2026_02_13_100002_add_fulfillment_to_orderproducts_table.php';
            }
            return response()->json(['status' => false, 'message' => 'Failed to update tracking: ' . $msg], 500);
        }

        $meta = $this->touchStatusSync($order, false);

        return response()->json([
            'status' => true,
            'message' => 'Tracking updated',
            'data' => [
                'order_id' => $order->id,
                'customer_status' => $meta['customer_status'],
                'steadfast_status' => $meta['steadfast_status'],
            ],
        ]);
    }
}
