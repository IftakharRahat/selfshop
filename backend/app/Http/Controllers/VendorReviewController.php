<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorReviewController extends Controller
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
     * GET /api/vendor/reviews
     * List all vendor products that have reviews, with rating summary.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $productIds = Product::where('vendor_id', $vendor->id)->pluck('id');

        if ($productIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'data' => ['products' => []],
            ]);
        }

        // Get review summary per product
        $reviewSummary = Review::whereIn('product_id', $productIds)
            ->select(
                'product_id',
                DB::raw('ROUND(AVG(rating), 1) as avg_rating'),
                DB::raw('COUNT(*) as review_count'),
                DB::raw('SUM(CASE WHEN status = "Active" THEN 0 ELSE 1 END) as new_count')
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $query = Product::whereIn('id', $reviewSummary->keys())
            ->select('id', 'ProductName', 'ProductSlug', 'ViewProductImage');

        if ($request->filled('search')) {
            $query->where('ProductName', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('rating')) {
            $ratingFilter = (float) $request->rating;
            $filteredIds = $reviewSummary->filter(fn($s) => floor($s->avg_rating) == $ratingFilter)->keys();
            $query->whereIn('id', $filteredIds);
        }

        $products = $query->orderByDesc('id')->get();

        $result = $products->map(function ($p) use ($reviewSummary) {
            $summary = $reviewSummary->get($p->id);
            return [
                'id' => $p->id,
                'ProductName' => $p->ProductName,
                'ProductSlug' => $p->ProductSlug,
                'ViewProductImage' => $p->ViewProductImage,
                'avg_rating' => $summary ? (float) $summary->avg_rating : 0,
                'review_count' => $summary ? (int) $summary->review_count : 0,
                'new_count' => $summary ? (int) $summary->new_count : 0,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => ['products' => $result->values()],
        ]);
    }

    /**
     * GET /api/vendor/reviews/{productId}
     * Get all reviews for a specific vendor product.
     */
    public function show($productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        // Ensure product belongs to vendor
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($productId);

        $reviews = Review::where('product_id', $product->id)
            ->with(['user:id,name,email,profile'])
            ->orderByDesc('created_at')
            ->get();

        $avgRating = Review::where('product_id', $product->id)->avg('rating');

        return response()->json([
            'status' => true,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'ProductName' => $product->ProductName,
                    'ViewProductImage' => $product->ViewProductImage,
                ],
                'avg_rating' => round($avgRating, 1),
                'review_count' => $reviews->count(),
                'reviews' => $reviews,
            ],
        ]);
    }
}
