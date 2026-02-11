<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\VendorCategoryDiscount;
use Illuminate\Http\Request;

class AdminVendorCategoryDiscountController extends Controller
{
    /**
     * List all vendor category discounts for admin.
     * GET /admin/vendor-category-discounts
     */
    public function index(Request $request)
    {
        $query = VendorCategoryDiscount::with(['vendor:id,company_name,user_id', 'vendor.user:id,email', 'category:id,category_name'])
            ->orderByDesc('updated_at');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $discounts = $query->paginate(30);

        return view('backend.content.vendor_category_discounts.index', compact('discounts'));
    }
}
