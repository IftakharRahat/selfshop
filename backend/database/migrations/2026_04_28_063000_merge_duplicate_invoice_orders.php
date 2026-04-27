<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════
        // STEP 1: Find orders with duplicate invoiceIDs
        //         (3 orders with same SS00441 → merge into 1)
        // ══════════════════════════════════════════════════════════
        $duplicateInvoices = DB::table('orders')
            ->select('invoiceID', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('invoiceID')
            ->where('invoiceID', '!=', '')
            ->groupBy('invoiceID')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $mergedOrders = 0;

        foreach ($duplicateInvoices as $dup) {
            $orders = DB::table('orders')
                ->where('invoiceID', $dup->invoiceID)
                ->orderBy('id')
                ->get();

            if ($orders->count() <= 1) continue;

            // Keep the first order
            $keepOrder = $orders->first();
            $deleteOrderIds = $orders->slice(1)->pluck('id')->toArray();

            Log::info("[MergeOrders] Invoice {$dup->invoiceID}: merging {$orders->count()} orders → keeping ID {$keepOrder->id}, deleting IDs " . implode(',', $deleteOrderIds));

            // Collect all orderproducts from ALL duplicate orders
            $allProducts = DB::table('orderproducts')
                ->whereIn('order_id', $orders->pluck('id')->toArray())
                ->get();

            // Move all orderproducts to the first order
            DB::table('orderproducts')
                ->whereIn('order_id', $deleteOrderIds)
                ->update(['order_id' => $keepOrder->id]);

            // Now aggregate duplicates within the merged order
            $productGroups = DB::table('orderproducts')
                ->where('order_id', $keepOrder->id)
                ->select('order_id', 'product_id', 'color', 'size',
                    DB::raw('COUNT(*) as cnt'),
                    DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('order_id', 'product_id', 'color', 'size')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($productGroups as $pg) {
                $rows = DB::table('orderproducts')
                    ->where('order_id', $keepOrder->id)
                    ->where('product_id', $pg->product_id)
                    ->where('color', $pg->color)
                    ->where('size', $pg->size)
                    ->orderBy('id')
                    ->get();

                if ($rows->count() <= 1) continue;

                $keepId = $rows->first()->id;
                $delIds = $rows->slice(1)->pluck('id')->toArray();

                DB::table('orderproducts')
                    ->where('id', $keepId)
                    ->update(['quantity' => $pg->total_qty]);

                DB::table('orderproducts')
                    ->whereIn('id', $delIds)
                    ->delete();

                Log::info("[MergeOrders] Invoice {$dup->invoiceID}: merged {$rows->count()} product rows → qty={$pg->total_qty}");
            }

            // Delete duplicate order's customers
            DB::table('customers')
                ->whereIn('order_id', $deleteOrderIds)
                ->delete();

            // Delete duplicate orders
            DB::table('orders')
                ->whereIn('id', $deleteOrderIds)
                ->delete();

            $mergedOrders += count($deleteOrderIds);
        }

        Log::info("[MergeOrders] Deleted {$mergedOrders} duplicate orders");

        // ══════════════════════════════════════════════════════════
        // STEP 2: Restore empty orderproducts from products table
        //         (handles orders where Update wiped product data)
        // ══════════════════════════════════════════════════════════

        // First, find orders that have ZERO orderproducts but had products from cart
        // We can't fully restore these without logs, but we can check

        // Restore empty names/codes from products table
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
                Log::info("[MergeOrders] Restored OrderProduct {$op->id}: " . implode(', ', array_keys($updates)));
            }
        }

        // ══════════════════════════════════════════════════════════
        // STEP 3: Recalculate subTotal for all affected orders
        // ══════════════════════════════════════════════════════════
        $affectedIds = collect($duplicateInvoices->pluck('invoiceID'))
            ->flatMap(function ($inv) {
                return DB::table('orders')->where('invoiceID', $inv)->pluck('id');
            })
            ->merge($broken->pluck('order_id'))
            ->unique();

        foreach ($affectedIds as $orderId) {
            $order = DB::table('orders')->where('id', $orderId)->first();
            if (!$order) continue;

            $buy = DB::table('orderproducts')
                ->where('order_id', $orderId)
                ->selectRaw('SUM(COALESCE(productPrice, 0) * COALESCE(quantity, 1)) as total')
                ->value('total') ?? 0;

            $newSubTotal = $buy + ($order->profit ?? 0);

            if (abs(($order->subTotal ?? 0) - $newSubTotal) > 1) {
                DB::table('orders')->where('id', $orderId)->update(['subTotal' => $newSubTotal]);
                Log::info("[MergeOrders] Order {$orderId}: subTotal {$order->subTotal} → {$newSubTotal}");
            }
        }
    }

    public function down(): void
    {
        // Data merge cannot be reversed
    }
};
