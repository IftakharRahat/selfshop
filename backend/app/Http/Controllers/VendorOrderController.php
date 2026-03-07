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

        $vendorSubtotal = $vendorOrderProducts->sum(fn($op) => (float) $op->productPrice * (int) $op->quantity);
        $customer = $order->customer;

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
                    'shipped_at' => $order->shipped_at?->toIso8601String(),
                ],

                'line_items' => $vendorOrderProducts->values()->map(fn($op) => [
                    'id' => $op->id,
                    'product_id' => $op->product_id,
                    'productName' => $op->productName,
                    'productCode' => $op->productCode,
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

        if (!$this->canVendorMutateWholeOrder($order, (int) $vendor->id)) {
            return response()->json([
                'status' => false,
                'message' => 'This order has products from multiple vendors. Only admin can change overall order status.',
            ], 409);
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

        if (!$this->canVendorMutateWholeOrder($order, (int) $vendor->id)) {
            return response()->json([
                'status' => false,
                'message' => 'This order has products from multiple vendors. Only admin can send whole order to warehouse.',
            ], 409);
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

        $created = $this->steadfastService->createConsignment(
            $order,
            (string) $customer->customerName,
            (string) $customer->customerAddress,
            (string) $customer->customerPhone
        );

        if (!$created['ok']) {
            return response()->json([
                'status' => false,
                'message' => $created['message'] ?? 'Failed to send order to warehouse',
                'data' => [
                    'provider_payload' => $created['payload'] ?? null,
                ],
            ], 502);
        }

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
        $order->warehouse_sent_at = now();
        $order->shipped_at = $order->shipped_at ?? now();
        $order->status = 'Ontheway';
        $order->save();

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

        return response()->json([
            'status' => true,
            'message' => 'Order sent to warehouse',
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'customer_status' => $meta['customer_status'],
                'steadfast_status' => $meta['steadfast_status'],
                'tracking_number' => $order->tracking_number,
                'tracking_link' => $order->trackingLink,
                'warehouse_sent_at' => $order->warehouse_sent_at?->toIso8601String(),
            ],
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
