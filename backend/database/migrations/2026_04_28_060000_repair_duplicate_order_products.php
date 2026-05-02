<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ── STEP 1: Merge duplicate order products ──
        $duplicates = DB::table('orderproducts')
            ->select('order_id', 'product_id', 'color', 'size', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('order_id', 'product_id', 'color', 'size')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('orderproducts')
                ->where('order_id', $dup->order_id)
                ->where('product_id', $dup->product_id)
                ->where('color', $dup->color)
                ->where('size', $dup->size)
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) continue;

            $keepId = $rows->first()->id;
            $deleteIds = $rows->slice(1)->pluck('id')->toArray();

            // Update first row with combined qty
            DB::table('orderproducts')->where('id', $keepId)->update([
                'quantity' => $dup->total_qty,
            ]);

            // Delete duplicates
            DB::table('orderproducts')->whereIn('id', $deleteIds)->delete();

            Log::info("[RepairMigration] Order {$dup->order_id}: merged {$rows->count()} duplicate rows into 1 (qty={$dup->total_qty})");
        }

        // ── STEP 2: Restore missing product names/codes/prices ──
        $broken = DB::table('orderproducts')
            ->where(function ($q) {
                $q->whereNull('productName')
                  ->orWhere('productName', '')
                  ->orWhereNull('productCode')
                  ->orWhere('productCode', '');
            })
            ->whereNotNull('product_id')
            ->get();

        foreach ($broken as $op) {
            $product = DB::table('products')->where('id', $op->product_id)->first();
            if (!$product) continue;

            $updates = [];
            if (empty($op->productName)) {
                $updates['productName'] = $product->ProductName;
            }
            if (empty($op->productCode)) {
                $updates['productCode'] = $product->ProductSku;
            }
            if (empty($op->productPrice) || $op->productPrice == 0) {
                $updates['productPrice'] = $product->ProductResellerPrice ?? $product->ProductPrice ?? 0;
            }

            if (!empty($updates)) {
                DB::table('orderproducts')->where('id', $op->id)->update($updates);
                Log::info("[RepairMigration] OrderProduct {$op->id}: restored " . implode(', ', array_keys($updates)));
            }
        }

        // ── STEP 3: Recalculate subTotal for affected orders ──
        $affectedOrderIds = $duplicates->pluck('order_id')
            ->merge($broken->pluck('order_id'))
            ->unique();

        foreach ($affectedOrderIds as $orderId) {
            $order = DB::table('orders')->where('id', $orderId)->first();
            if (!$order) continue;

            $buy = DB::table('orderproducts')
                ->where('order_id', $orderId)
                ->selectRaw('SUM(COALESCE(productPrice, 0) * COALESCE(quantity, 1)) as total')
                ->value('total') ?? 0;

            $newSubTotal = $buy + ($order->profit ?? 0);

            if (abs(($order->subTotal ?? 0) - $newSubTotal) > 1) {
                DB::table('orders')->where('id', $orderId)->update(['subTotal' => $newSubTotal]);
                Log::info("[RepairMigration] Order {$orderId}: subTotal {$order->subTotal} → {$newSubTotal}");
            }
        }
    }

    public function down(): void
    {
        // Data repair cannot be reversed
    }
};
