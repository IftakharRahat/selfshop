<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════
        // STEP 1: Remove duplicate customer records
        //         (3 customer rows for same order_id → keeps 1)
        //         This is the ROOT CAUSE of orders showing 3x in list
        // ══════════════════════════════════════════════════════════

        $duplicateCustomers = DB::table('customers')
            ->select('order_id', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $deletedCustomers = 0;

        foreach ($duplicateCustomers as $dup) {
            // Delete all except the first customer record
            $deleted = DB::table('customers')
                ->where('order_id', $dup->order_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();

            $deletedCustomers += $deleted;
            Log::info("[FixDupCustomers] Order {$dup->order_id}: deleted {$deleted} duplicate customer records (kept ID {$dup->keep_id})");
        }

        Log::info("[FixDupCustomers] Total duplicate customers removed: {$deletedCustomers}");

        // ══════════════════════════════════════════════════════════
        // STEP 2: Merge duplicate orderproducts within same order
        //         (3 rows of same product → 1 row with combined qty)
        // ══════════════════════════════════════════════════════════

        $duplicateProducts = DB::table('orderproducts')
            ->select('order_id', 'product_id', 'color', 'size',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('MIN(id) as keep_id'))
            ->groupBy('order_id', 'product_id', 'color', 'size')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $mergedProducts = 0;

        foreach ($duplicateProducts as $dup) {
            // Update the kept row with combined quantity
            DB::table('orderproducts')
                ->where('id', $dup->keep_id)
                ->update(['quantity' => $dup->total_qty]);

            // Delete the duplicates
            $deleted = DB::table('orderproducts')
                ->where('order_id', $dup->order_id)
                ->where('product_id', $dup->product_id)
                ->where('color', $dup->color)
                ->where('size', $dup->size)
                ->where('id', '!=', $dup->keep_id)
                ->delete();

            $mergedProducts += $deleted;
            Log::info("[FixDupCustomers] Order {$dup->order_id}: merged {$dup->cnt} product rows → qty={$dup->total_qty}");
        }

        Log::info("[FixDupCustomers] Total duplicate orderproducts merged: {$mergedProducts}");

        // ══════════════════════════════════════════════════════════
        // STEP 3: Restore empty/missing product info
        // ══════════════════════════════════════════════════════════

        $broken = DB::table('orderproducts')
            ->where(function ($q) {
                $q->whereNull('productName')
                  ->orWhere('productName', '')
                  ->orWhereNull('productCode')
                  ->orWhere('productCode', '')
                  ->orWhere('productPrice', 0)
                  ->orWhereNull('productPrice');
            })
            ->whereNotNull('product_id')
            ->where('product_id', '>', 0)
            ->get();

        $restored = 0;

        foreach ($broken as $op) {
            $product = DB::table('products')->where('id', $op->product_id)->first();
            if (!$product) continue;

            $updates = [];
            if (empty($op->productName)) {
                $updates['productName'] = $product->ProductName;
            }
            if (empty($op->productCode)) {
                $updates['productCode'] = $product->ProductSku ?? '';
            }
            if (empty($op->productPrice) || $op->productPrice == 0) {
                $updates['productPrice'] = $product->ProductResellerPrice ?? $product->ProductPrice ?? 0;
            }

            if (!empty($updates)) {
                DB::table('orderproducts')->where('id', $op->id)->update($updates);
                $restored++;
                Log::info("[FixDupCustomers] OrderProduct {$op->id} (Order {$op->order_id}): restored " . implode(', ', array_keys($updates)));
            }
        }

        Log::info("[FixDupCustomers] Total orderproducts restored: {$restored}");

        // ══════════════════════════════════════════════════════════
        // STEP 4: For orders with ZERO orderproducts, try to
        //         recreate from the order's product_id if available
        // ══════════════════════════════════════════════════════════

        $emptyOrders = DB::table('orders')
            ->leftJoin('orderproducts', 'orders.id', '=', 'orderproducts.order_id')
            ->whereNull('orderproducts.id')
            ->select('orders.*')
            ->get();

        $recreated = 0;

        foreach ($emptyOrders as $order) {
            // Check if the order has a product_id we can use to recreate
            if (!empty($order->product_id)) {
                $product = DB::table('products')->where('id', $order->product_id)->first();
                if ($product) {
                    DB::table('orderproducts')->insert([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'productCode' => $product->ProductSku ?? '',
                        'productName' => $product->ProductName ?? '',
                        'color' => 'Defa',
                        'size' => 'Defa',
                        'quantity' => 1,
                        'productPrice' => $product->ProductResellerPrice ?? $product->ProductPrice ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $recreated++;
                    Log::info("[FixDupCustomers] Order {$order->id}: recreated orderproduct from product_id={$product->id} ({$product->ProductName})");
                }
            }
        }

        Log::info("[FixDupCustomers] Total orderproducts recreated: {$recreated}");
    }

    public function down(): void
    {
        // Data repair cannot be reversed
    }
};
