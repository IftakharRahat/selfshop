<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminVendorProductController extends Controller
{
    /**
     * List all products added by vendors (vendor_id not null).
     * GET /admin/vendor-products
     */
    public function index(Request $request)
    {
        $query = Product::with(['vendor:id,company_name,user_id', 'vendor.user:id,email', 'categories:id,category_name'])
            ->whereNotNull('vendor_id')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('vendor_approval_status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ProductName', 'like', '%' . $search . '%')
                    ->orWhere('ProductSku', 'like', '%' . $search . '%');
            });
        }

        $products = $query->paginate(20);

        return view('backend.content.vendor_products.index', compact('products'));
    }

    /**
     * Approve a vendor product. Sets vendor_approval_status = approved, status = Active.
     * POST /admin/vendor-products/{id}/approve
     */
    public function approve($id)
    {
        $product = Product::whereNotNull('vendor_id')->findOrFail($id);
        $product->vendor_approval_status = 'approved';
        $product->status = 'Active';
        $product->save();
        return redirect()->back()->with('message', 'Product approved. It is now visible on the storefront.');
    }

    /**
     * Reject a vendor product. Sets vendor_approval_status = rejected, status = Inactive.
     * POST /admin/vendor-products/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $product = Product::whereNotNull('vendor_id')->findOrFail($id);
        $product->vendor_approval_status = 'rejected';
        $product->status = 'Inactive';
        $product->save();
        return redirect()->back()->with('message', 'Product rejected.');
    }
}
