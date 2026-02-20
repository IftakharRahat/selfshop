<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorCampaignController extends Controller
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
     * GET /api/vendor/campaigns
     * List active campaigns open for vendor registration.
     */
    public function index()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $campaigns = FlashSale::where('status', 'Active')
            ->where('vendor_registration', true)
            ->withCount('products')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($campaign) use ($vendor) {
                $vendorProductCount = FlashSaleProduct::where('flash_sale_id', $campaign->id)
                    ->where('vendor_id', $vendor->id)
                    ->count();
                $campaign->vendor_product_count = $vendorProductCount;
                return $campaign;
            });

        return response()->json([
            'status' => true,
            'data' => ['campaigns' => $campaigns],
        ]);
    }

    /**
     * GET /api/vendor/campaigns/{id}
     * Single campaign details with vendor's submitted products.
     */
    public function show($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $campaign = FlashSale::withCount('products')->findOrFail($id);

        // Vendor's submitted products for this campaign
        $vendorProducts = FlashSaleProduct::where('flash_sale_id', $id)
            ->where('vendor_id', $vendor->id)
            ->with('product:id,ProductName,ViewProductImage,ProductSalePrice,ProductRegularPrice,qty,ProductSku')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'campaign' => $campaign,
                'vendor_products' => $vendorProducts,
            ],
        ]);
    }

    /**
     * POST /api/vendor/campaigns/{id}/products
     * Submit a product to a campaign.
     */
    public function submitProduct(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $campaign = FlashSale::where('status', 'Active')
            ->where('vendor_registration', true)
            ->findOrFail($id);

        // Check registration deadline
        if ($campaign->registration_deadline && now()->gt($campaign->registration_deadline)) {
            return response()->json(['status' => false, 'message' => 'Registration deadline has passed'], 422);
        }

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'campaign_price' => 'required|numeric|min:0',
            'seller_sku' => 'nullable|string|max:100',
        ]);

        // Verify the product belongs to this vendor
        $product = Product::where('id', $request->product_id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found or does not belong to you'], 404);
        }

        // Check if already submitted
        $exists = FlashSaleProduct::where('flash_sale_id', $id)
            ->where('product_id', $request->product_id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($exists) {
            return response()->json(['status' => false, 'message' => 'Product already submitted to this campaign'], 422);
        }

        // Calculate discount percentage from campaign price vs regular price
        $regularPrice = $product->ProductRegularPrice > 0 ? $product->ProductRegularPrice : $product->ProductSalePrice;
        $discountPercentage = 0;
        if ($regularPrice > 0 && $request->campaign_price < $regularPrice) {
            $discountPercentage = round((($regularPrice - $request->campaign_price) / $regularPrice) * 100, 2);
        }

        $fsp = new FlashSaleProduct();
        $fsp->flash_sale_id = $id;
        $fsp->product_id = $request->product_id;
        $fsp->vendor_id = $vendor->id;
        $fsp->campaign_price = $request->campaign_price;
        $fsp->seller_sku = $request->seller_sku;
        $fsp->discount_percentage = $discountPercentage;
        $fsp->save();

        $fsp->load('product:id,ProductName,ViewProductImage,ProductSalePrice,ProductRegularPrice,qty,ProductSku');

        return response()->json([
            'status' => true,
            'message' => 'Product submitted to campaign',
            'data' => ['flash_sale_product' => $fsp],
        ], 201);
    }

    /**
     * DELETE /api/vendor/campaigns/{id}/products/{fspId}
     * Remove vendor's own submitted product from a campaign.
     */
    public function removeProduct($id, $fspId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $fsp = FlashSaleProduct::where('id', $fspId)
            ->where('flash_sale_id', $id)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $fsp->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product removed from campaign',
        ]);
    }
}
