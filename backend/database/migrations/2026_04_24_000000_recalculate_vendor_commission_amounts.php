<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One-time data migration: recalculate commission_amount for all VendorEarning
 * records using the admin-configured category commission rate.
 *
 * Previously commission_amount = lineTotal - netAmount (full price markup),
 * which incorrectly included reseller profit margins.
 * Correct formula: commission_amount = netAmount × rate / 100
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_earnings') || !Schema::hasTable('vendor_commission_config')) {
            return;
        }

        $commissionService = app(\App\Services\VendorCommissionService::class);

        $earnings = DB::table('vendor_earnings')
            ->join('orderproducts', 'vendor_earnings.order_product_id', '=', 'orderproducts.id')
            ->join('products', 'orderproducts.product_id', '=', 'products.id')
            ->select(
                'vendor_earnings.id',
                'vendor_earnings.net_amount',
                'vendor_earnings.commission_amount as old_commission',
                'products.vendor_id',
                'products.category_id'
            )
            ->whereNotNull('products.vendor_id')
            ->where('products.vendor_id', '>', 0)
            ->get();

        $changed = 0;
        foreach ($earnings as $earning) {
            $rate = $commissionService->getRateForProduct(
                (int) $earning->vendor_id,
                (int) $earning->category_id
            );
            $newCommission = round((float) $earning->net_amount * $rate / 100, 2);

            if (abs((float) $earning->old_commission - $newCommission) > 0.01) {
                DB::table('vendor_earnings')
                    ->where('id', $earning->id)
                    ->update([
                        'commission_percent' => $rate,
                        'commission_amount' => $newCommission,
                    ]);
                $changed++;
            }
        }

        Log::info("Migration: Recalculated vendor commissions. {$changed}/{$earnings->count()} records updated.");
    }

    public function down(): void
    {
        // Cannot reverse — old commission values not stored
    }
};
