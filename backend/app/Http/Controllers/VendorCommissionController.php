<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\VendorCommissionConfig;
use App\Services\VendorCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorCommissionController extends Controller
{
    public function __construct(
        protected VendorCommissionService $commissionService
    ) {}

    /**
     * GET /api/vendor/category-commissions
     * Returns active categories with effective commission for the authenticated vendor.
     */
    public function categoryCommissions(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $vendor = $user->vendor;

        $globalDefault = VendorCommissionConfig::whereNull('vendor_id')
            ->whereNull('category_id')
            ->value('commission_percent');
        $globalDefault = $globalDefault !== null ? (float) $globalDefault : 10.0;

        $categories = Category::where('status', 'Active')
            ->select('id', 'category_name', 'slug')
            ->orderBy('category_name')
            ->get()
            ->map(function ($category) use ($vendor) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->category_name,
                    'category_slug' => $category->slug,
                    'commission_percent' => $this->commissionService->getRateForProduct(
                        $vendor->id,
                        (int) $category->id
                    ),
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => [
                'global_default_commission_percent' => $globalDefault,
                'categories' => $categories,
            ],
        ]);
    }
}
