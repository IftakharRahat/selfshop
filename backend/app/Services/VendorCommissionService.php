<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\VendorCommissionConfig;
use App\Models\VendorEarning;
use Illuminate\Support\Facades\DB;

class VendorCommissionService
{
    protected static ?float $globalRate = null;

    /**
     * Get effective commission rate for a vendor product (vendor_id + category_id).
     * Priority:
     * 1) vendor + category
     * 2) global category (admin-set, applies to all vendors)
     * 3) vendor global (legacy fallback)
     * 4) global default
     */
    public function getRateForProduct(?int $vendorId, ?int $categoryId): float
    {
        if (!$vendorId) {
            return 0;
        }

        if ($categoryId) {
            $categoryRate = VendorCommissionConfig::where('vendor_id', $vendorId)
                ->where('category_id', $categoryId)
                ->value('commission_percent');

            if ($categoryRate !== null) {
                return (float) $categoryRate;
            }

            $globalCategoryRate = VendorCommissionConfig::whereNull('vendor_id')
                ->where('category_id', $categoryId)
                ->value('commission_percent');

            if ($globalCategoryRate !== null) {
                return (float) $globalCategoryRate;
            }
        }

        $vendorRate = VendorCommissionConfig::where('vendor_id', $vendorId)
            ->whereNull('category_id')
            ->value('commission_percent');

        if ($vendorRate !== null) {
            return (float) $vendorRate;
        }

        $global = VendorCommissionConfig::whereNull('vendor_id')
            ->whereNull('category_id')
            ->value('commission_percent');

        return $global !== null ? (float) $global : 10.0; // default 10%
    }

    /**
     * Ensure vendor_earnings rows exist for an order. Creates them for each orderproduct that belongs to a vendor.
     */
    public function syncEarningsForOrder(int $orderId): void
    {
        $order = Order::with(['orderproducts.product'])->find($orderId);
        if (!$order) {
            return;
        }

        foreach ($order->orderproducts as $op) {
            $product = $op->product;
            if (!$product || !$product->vendor_id) {
                continue;
            }

            if (VendorEarning::where('order_product_id', $op->id)->exists()) {
                continue;
            }

            // Vendor receives exactly their base price (ProductResellerPrice)
            $basePrice = (float) ($product->ProductResellerPrice ?? $op->productPrice);
            $netAmount = round($basePrice * (int) $op->quantity, 2);

            // Storefront price (commission inclusive)
            $lineTotal = round((float) $op->productPrice * (int) $op->quantity, 2);

            // Admin commission is the difference
            $commissionAmount = round($lineTotal - $netAmount, 2);
            $rate = $this->getRateForProduct($product->vendor_id, $product->category_id);

            $status = in_array($order->status, ['Delivered', 'Shipped'], true) ? 'available' : 'pending';

            VendorEarning::create([
                'vendor_id' => $product->vendor_id,
                'order_id' => $order->id,
                'order_product_id' => $op->id,
                'line_total' => $lineTotal,
                'commission_percent' => $rate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => $status,
            ]);
        }
    }

    /**
     * Update earnings status to 'available' when order is delivered/shipped.
     */
    public function markEarningsAvailableForOrder(int $orderId): void
    {
        VendorEarning::where('order_id', $orderId)->update(['status' => 'available']);
    }

    public function getStorefrontPrice(float $basePrice, ?int $vendorId, ?int $categoryId): float
    {
        $rate = $this->getRateForProduct($vendorId, $categoryId);
        return round($basePrice * (1 + $rate / 100), 2);
    }

    /**
     * Set or get default global commission percent (for seeding).
     */
    public static function ensureGlobalCommission(float $percent = 10.0): void
    {
        VendorCommissionConfig::firstOrCreate(
            ['vendor_id' => null, 'category_id' => null],
            ['commission_percent' => $percent]
        );
    }
}
