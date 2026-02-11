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
}
