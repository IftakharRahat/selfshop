<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VendorCommissionConfig;
use App\Services\VendorAdminNotificationService;
use Illuminate\Http\Request;

class AdminVendorCommissionController extends Controller
{
    public function __construct(
        protected VendorAdminNotificationService $vendorNotificationService
    ) {}

    /**
     * GET /admin/vendor-category-commissions
     * Global category-wise commission configuration for vendors.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $categories = Category::query()
            ->where('status', 'Active')
            ->when($search !== '', function ($q) use ($search) {
                $q->where('category_name', 'like', '%' . $search . '%');
            })
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'slug']);

        $commissionRows = VendorCommissionConfig::whereNull('vendor_id')
            ->whereNotNull('category_id')
            ->pluck('commission_percent', 'category_id');

        $globalDefault = VendorCommissionConfig::whereNull('vendor_id')
            ->whereNull('category_id')
            ->value('commission_percent');

        return view('backend.content.vendor_commission.index', [
            'categories' => $categories,
            'commissionRows' => $commissionRows,
            'globalDefault' => $globalDefault !== null ? (float) $globalDefault : 10.0,
            'search' => $search,
        ]);
    }

    /**
     * POST /admin/vendor-category-commissions/{category}
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        VendorCommissionConfig::updateOrCreate(
            [
                'vendor_id' => null,
                'category_id' => $category->id,
            ],
            [
                'commission_percent' => round((float) $validated['commission_percent'], 2),
            ]
        );

        // Recalculate ProductPrice for all vendor products in this category
        $products = \App\Models\Product::where('category_id', $category->id)
            ->whereNotNull('vendor_id')
            ->get();

        $commissionService = app(\App\Services\VendorCommissionService::class);
        foreach ($products as $product) {
            $displayPrice = $commissionService->getStorefrontPrice(
                (float) $product->ProductResellerPrice,
                (int) $product->vendor_id,
                (int) $product->category_id
            );
            $product->ProductRegularPrice = $displayPrice;
            $product->ProductSalePrice = $displayPrice;
            $product->save();
        }

        // Commission update notification removed per client request —
        // suppliers should not be notified about commission changes.


        return redirect()->back()->with('message', 'Commission updated for ' . $category->category_name . '.');
    }
}
