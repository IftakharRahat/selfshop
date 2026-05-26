<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\WarrantyClaim;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WarrantyClaimController extends Controller
{
    /**
     * GET /api/warranty/products
     *
     * Returns delivered orders with warranty-eligible products
     * (warranty period still active, not already claimed).
     */
    public function products(): JsonResponse
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        // 1. Get all delivered orders for this user that have a deliveryDate
        $orders = Order::where('user_id', $userId)
            ->where('status', 'Delivered')
            ->whereNotNull('deliveryDate')
            ->with(['orderproducts', 'customer'])
            ->orderByDesc('deliveryDate')
            ->get();

        $result = [];

        foreach ($orders as $order) {
            $orderProducts = [];

            foreach ($order->orderproducts as $op) {
                $product = Product::find($op->product_id);
                if (!$product) continue;

                // Skip products without warranty
                $warrantyDays = $product->warranty_days;
                if (!$warrantyDays || $warrantyDays <= 0) continue;

                $deliveredAt = Carbon::parse($order->deliveryDate);
                $expiresAt = $deliveredAt->copy()->addDays($warrantyDays);

                // Skip if warranty expired
                if ($expiresAt->lt(Carbon::today())) continue;

                $daysLeft = Carbon::today()->diffInDays($expiresAt, false);

                // Check if already claimed
                $alreadyClaimed = WarrantyClaim::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->where('order_product_id', $op->id)
                    ->exists();

                $orderProducts[] = [
                    'order_product_id' => $op->id,
                    'product_id'       => $product->id,
                    'product_name'     => $op->productName ?? $product->ProductName,
                    'product_code'     => $op->productCode ?? $product->ProductCode,
                    'product_image'    => $product->ViewProductImage,
                    'product_price'    => $op->productPrice,
                    'quantity'         => $op->quantity,
                    'warranty_days'    => $warrantyDays,
                    'delivered_at'     => $order->deliveryDate,
                    'expires_at'       => $expiresAt->toDateString(),
                    'days_left'        => max(0, $daysLeft),
                    'already_claimed'  => $alreadyClaimed,
                    'vendor_id'        => $product->vendor_id,
                ];
            }

            if (count($orderProducts) > 0) {
                $result[] = [
                    'order_id'    => $order->id,
                    'invoice_id'  => $order->invoiceID,
                    'order_date'  => $order->orderDate,
                    'delivered_at' => $order->deliveryDate,
                    'customer_name' => $order->customer->customerName ?? null,
                    'products'    => $orderProducts,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data'   => $result,
        ]);
    }

    /**
     * POST /api/warranty/claims
     *
     * Submit a warranty/refund claim.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_id'         => 'required|integer|exists:orders,id',
            'product_id'       => 'required|integer|exists:products,id',
            'order_product_id' => 'required|integer|exists:orderproducts,id',
            'reason'           => 'required|string|min:10|max:2000',
            'images'           => 'nullable|array|max:3',
            'images.*'         => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $userId = Auth::id();

        // Verify the order belongs to this user and is delivered
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $userId)
            ->where('status', 'Delivered')
            ->whereNotNull('deliveryDate')
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Order not found or not eligible for warranty claim.',
            ], 422);
        }

        // Verify the product has warranty and it's still active
        $product = Product::find($request->product_id);
        if (!$product || !$product->warranty_days || $product->warranty_days <= 0) {
            return response()->json([
                'status'  => false,
                'message' => 'This product does not have a warranty.',
            ], 422);
        }

        $deliveredAt = Carbon::parse($order->deliveryDate);
        $expiresAt = $deliveredAt->copy()->addDays($product->warranty_days);

        if ($expiresAt->lt(Carbon::today())) {
            return response()->json([
                'status'  => false,
                'message' => 'Warranty period has expired for this product.',
            ], 422);
        }

        // Check for duplicate claim
        $exists = WarrantyClaim::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->where('order_product_id', $request->order_product_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'A refund claim already exists for this product in this order.',
            ], 422);
        }

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('warranty-claims', 'public');
                $imagePaths[] = $path;
            }
        }

        // Create the claim
        $claim = WarrantyClaim::create([
            'order_id'           => $order->id,
            'order_product_id'   => $request->order_product_id,
            'product_id'         => $product->id,
            'user_id'            => $userId,
            'vendor_id'          => $product->vendor_id,
            'warranty_days'      => $product->warranty_days,
            'delivered_at'       => $order->deliveryDate,
            'warranty_expires_at' => $expiresAt->toDateString(),
            'reason'             => $request->reason,
            'images'             => count($imagePaths) > 0 ? $imagePaths : null,
            'status'             => 'pending',
        ]);

        Log::info('WarrantyClaim: new claim submitted', [
            'claim_id'     => $claim->id,
            'claim_number' => $claim->claim_number,
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'user_id'      => $userId,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Refund claim submitted successfully.',
            'data'    => [
                'claim_id'     => $claim->id,
                'claim_number' => $claim->claim_number,
                'status'       => $claim->status,
            ],
        ], 201);
    }

    /**
     * GET /api/warranty/claims
     *
     * List the authenticated user's warranty claims.
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        $claims = WarrantyClaim::where('user_id', $userId)
            ->with(['product', 'order'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (WarrantyClaim $claim) {
                return [
                    'id'              => $claim->id,
                    'claim_number'    => $claim->claim_number,
                    'order_id'        => $claim->order_id,
                    'invoice_id'      => $claim->order->invoiceID ?? null,
                    'product_id'      => $claim->product_id,
                    'product_name'    => $claim->product->ProductName ?? null,
                    'product_image'   => $claim->product->ViewProductImage ?? null,
                    'warranty_days'   => $claim->warranty_days,
                    'delivered_at'    => $claim->delivered_at->toDateString(),
                    'expires_at'      => $claim->warranty_expires_at->toDateString(),
                    'days_left'       => max(0, Carbon::today()->diffInDays($claim->warranty_expires_at, false)),
                    'reason'          => $claim->reason,
                    'images'          => $claim->images,
                    'status'          => $claim->status,
                    'admin_note'      => $claim->admin_note,
                    'responded_at'    => $claim->responded_at?->toDateTimeString(),
                    'created_at'      => $claim->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $claims,
        ]);
    }
}
