<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductPriceTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * API for Vendor (Wholesale) – used by Next.js vendor subdomain.
 * All routes require auth:sanctum. Optionally restrict to is_verified_wholesaler.
 */
class VendorApiController extends Controller
{
    /**
     * Product details for vendor bulk-order matrix (variants + price tiers).
     * GET /api/vendor/product-details/{slug}
     */
    public function productDetails(Request $request, string $slug)
    {
        $product = Product::with(['varients', 'priceTiers'])
            ->where('ProductSlug', $slug)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $tiers = $product->priceTiers->isEmpty()
            ? $this->defaultTiers($product)
            : $product->priceTiers->map(fn ($t) => [
                'min_qty' => (int) $t->min_qty,
                'unit_price' => (float) $t->unit_price,
                'tier_label' => $t->tier_label,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Product details for vendor',
            'data' => [
                'product_details' => $product,
                'price_tiers' => $tiers,
            ],
        ], 200);
    }

    /**
     * Bulk add to cart: multiple variant lines in one request.
     * POST /api/vendor/bulk-add-to-cart
     * Body: { product_id, items: [ { variant_label, size?, variant_id?, qty, unit_price } ] }
     */
    public function bulkAddToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'items' => 'required|array|min:1',
            'items.*.variant_label' => 'required|string|max:255',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.variant_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $userId = Auth::id();
        $created = [];

        foreach ($request->items as $item) {
            if (($item['qty'] ?? 0) < 1) {
                continue;
            }
            $cart = Cart::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'name' => $product->ProductName,
                'code' => $product->ProductSku,
                'price' => (float) $item['unit_price'],
                'qty' => (int) $item['qty'],
                'size' => $item['size'] ?? '',
                'color' => $item['color'] ?? '',
                'image' => $product->ViewProductImage ?? $product->ProductImage,
                'options' => [
                    'variant_label' => $item['variant_label'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'size' => $item['size'] ?? null,
                    'bulk_order' => true,
                ],
            ]);
            $created[] = $cart;
        }

        return response()->json([
            'status' => true,
            'message' => count($created) . ' item(s) added to cart',
            'data' => ['carts' => $created],
        ], 201);
    }

    /**
     * Default tiers when product has none: 0+, 50+, 100+ with 5% discount per tier.
     */
    private function defaultTiers(Product $product): array
    {
        $base = (float) ($product->ProductResellerPrice ?? $product->min_sell_price ?? 0);
        return [
            ['min_qty' => 0, 'unit_price' => $base, 'tier_label' => 'Tier 1'],
            ['min_qty' => 50, 'unit_price' => round($base * 0.95, 2), 'tier_label' => 'Tier 2'],
            ['min_qty' => 100, 'unit_price' => round($base * 0.90, 2), 'tier_label' => 'Tier 3'],
        ];
    }
}
