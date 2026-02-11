<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\VendorCategoryDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorCategoryDiscountController extends Controller
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
     * GET /api/vendor/category-discounts
     * List all categories with the vendor's discount info.
     */
    public function index()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $categories = Category::where('status', 'Active')
            ->orderBy('id')
            ->get(['id', 'category_name', 'category_icon', 'slug']);

        $discounts = VendorCategoryDiscount::where('vendor_id', $vendor->id)
            ->get()
            ->keyBy('category_id');

        $result = $categories->map(function ($cat) use ($discounts) {
            $d = $discounts->get($cat->id);
            return [
                'id' => $cat->id,
                'category_name' => $cat->category_name,
                'category_icon' => $cat->category_icon,
                'slug' => $cat->slug,
                'discount_percent' => $d ? (float) $d->discount_percent : 0,
                'start_date' => $d ? ($d->start_date ? $d->start_date->format('Y-m-d') : null) : null,
                'end_date' => $d ? ($d->end_date ? $d->end_date->format('Y-m-d') : null) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => ['categories' => $result->values()],
        ]);
    }

    /**
     * POST /api/vendor/category-discounts/{categoryId}
     * Set or update discount for a category.
     */
    public function set(Request $request, $categoryId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $request->validate([
            'discount_percent' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $category = Category::findOrFail($categoryId);

        $discount = VendorCategoryDiscount::updateOrCreate(
            ['vendor_id' => $vendor->id, 'category_id' => $category->id],
            [
                'discount_percent' => $request->discount_percent,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Category discount updated',
            'data' => ['discount' => $discount],
        ]);
    }
}
