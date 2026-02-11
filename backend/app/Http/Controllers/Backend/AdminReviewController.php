<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    /**
     * List all product reviews for admin.
     * GET /admin/reviews
     */
    public function index(Request $request)
    {
        $query = Review::with(['product:id,ProductName,ViewProductImage,vendor_id', 'product.vendor:id,company_name', 'user:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('ProductName', 'like', '%' . $search . '%');
            });
        }

        $reviews = $query->paginate(20);

        return view('backend.content.reviews.index', compact('reviews'));
    }

    /**
     * Update review status (e.g. Active / Inactive).
     * POST /admin/reviews/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Active,Inactive']);
        $review = Review::findOrFail($id);
        $review->status = $request->status;
        $review->save();
        return redirect()->back()->with('message', 'Review status updated.');
    }
}
