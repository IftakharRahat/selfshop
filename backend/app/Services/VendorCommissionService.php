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
     * Priority: vendor-specific > category > global.
     */
    public function getRateForProduct(?int $vendorId, ?int $categoryId): float
    {
        if (!$vendorId) {
            return 0;
        }

        $vendorRate = VendorCommissionConfig::where('vendor_id', $vendorId)
            ->whereNull('category_id')
            ->value('commission_percent');

        if ($vendorRate !== null) {
            return (float) $vendorRate;
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

            $lineTotal = (float) $op->productPrice * (int) $op->quantity;
            $rate = $this->getRateForProduct($product->vendor_id, $product->category_id);
            $commissionAmount = round($lineTotal * $rate / 100, 2);
            $netAmount = round($lineTotal - $commissionAmount, 2);

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
