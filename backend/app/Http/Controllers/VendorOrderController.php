<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Orderproduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorOrderController extends Controller
{
    private function getVendor()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }
        return $user->vendor;
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
            $vendorItems = $order->orderproducts->filter(fn($op) => $op->product && $op->product->vendor_id == $vendor->id);
            $vendorSubtotal = $vendorItems->sum(fn($op) => (float) $op->productPrice * (int) $op->quantity);
            $orderDate = $order->orderDate;
            if ($orderDate instanceof \DateTimeInterface) {
                $orderDate = $orderDate->format('Y-m-d');
            }
            return [
                'id' => $order->id,
                'invoiceID' => $order->invoiceID,
                'orderDate' => $orderDate,
                'status' => $order->status,
                'Payment' => $order->Payment,
                'paymentAmount' => $order->paymentAmount,
                'subTotal' => $order->subTotal,
                'customer_name' => $order->customer?->customerName,
                'customer_phone' => $order->customer?->customerPhone,
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
     * Single order detail: only line items that belong to this vendor, plus order and customer info.
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

        $vendorOrderProducts = $order->orderproducts->filter(fn($op) => $op->product && $op->product->vendor_id == $vendor->id);
        if ($vendorOrderProducts->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Order not found or contains no your products'], 404);
        }

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
                    'Payment' => $order->Payment,
                    'paymentAmount' => $order->paymentAmount,
                    'subTotal' => $order->subTotal,
                    'deliveryCharge' => $order->deliveryCharge,
                    'discountCharge' => $order->discountCharge,
                    'customerNote' => $order->customerNote,
                    'tracking_number' => $order->tracking_number,
                    'shipped_at' => $order->shipped_at?->toIso8601String(),
                ],
                'customer' => $customer ? [
                    'customerName' => $customer->customerName,
                    'customerPhone' => $customer->customerPhone,
                    'customerAddress' => $customer->customerAddress,
                ] : null,
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

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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
                $order->shipped_at = $order->shipped_at ?? now();
                $order->status = 'Shipped';
                $order->save();
            } else {
                // If we only updated line items, check if all vendor items are now shipped → update order
                $updated = Orderproduct::whereIn('id', $vendorOrderProductIds)->get();
                $allShipped = $updated->every(fn($op) => ($op->fulfillment_status ?? 'pending') === 'shipped');
                if ($allShipped && $updated->whereNotNull('tracking_number')->isNotEmpty()) {
                    $order->shipped_at = $order->shipped_at ?? now();
                    if (in_array($order->status, ['Processing', 'Pending'], true)) {
                        $order->status = 'Shipped';
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

        return response()->json([
            'status' => true,
            'message' => 'Tracking updated',
            'data' => ['order_id' => $order->id],
        ]);
    }
}
