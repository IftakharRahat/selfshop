<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\Order;

class RepairOrderProducts extends Command
{
    protected $signature = 'orders:repair {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Merge duplicate order products and restore missing product names/prices from products table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  STEP 1: Merge Duplicate Order Products');
        $this->info('═══════════════════════════════════════');

        // Find orders with duplicate products (same order_id + product_id + color + size)
        $duplicates = DB::table('orderproducts')
            ->select('order_id', 'product_id', 'color', 'size', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('order_id', 'product_id', 'color', 'size')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $mergedCount = 0;
        foreach ($duplicates as $dup) {
            $rows = Orderproduct::where('order_id', $dup->order_id)
                ->where('product_id', $dup->product_id)
                ->where('color', $dup->color)
                ->where('size', $dup->size)
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) continue;

            $keep = $rows->first();
            $totalQty = $rows->sum('quantity');
            $deleteIds = $rows->slice(1)->pluck('id');

            $order = Order::find($dup->order_id);
            $invoiceID = $order ? $order->invoiceID : 'N/A';

            $this->line("  Order #{$invoiceID} (ID:{$dup->order_id}): Product {$dup->product_id} has {$rows->count()} duplicate rows → merging to qty={$totalQty}");

            if (!$dryRun) {
                // Update the first row with combined quantity
                $keep->quantity = $totalQty;
                $keep->save();

                // Delete the duplicate rows
                Orderproduct::whereIn('id', $deleteIds)->delete();
            }

            $mergedCount++;
        }

        $this->info("  ✅ Merged {$mergedCount} duplicate product groups");

        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  STEP 2: Restore Missing Product Info');
        $this->info('═══════════════════════════════════════');

        // Find order products with missing/empty names or codes
        $broken = Orderproduct::where(function ($q) {
            $q->whereNull('productName')
              ->orWhere('productName', '')
              ->orWhereNull('productCode')
              ->orWhere('productCode', '');
        })
        ->whereNotNull('product_id')
        ->get();

        $restoredCount = 0;
        foreach ($broken as $op) {
            $product = Product::find($op->product_id);
            if (!$product) {
                $this->warn("  ⚠ OrderProduct #{$op->id} (Order:{$op->order_id}) - Product ID {$op->product_id} not found in products table");
                continue;
            }

            $changes = [];
            if (empty($op->productName)) {
                $changes[] = "name: '' → '{$product->ProductName}'";
                if (!$dryRun) $op->productName = $product->ProductName;
            }
            if (empty($op->productCode)) {
                $changes[] = "code: '' → '{$product->ProductSku}'";
                if (!$dryRun) $op->productCode = $product->ProductSku;
            }

            // Restore price if it's 0 or null
            if (empty($op->productPrice) || $op->productPrice == 0) {
                $restoredPrice = $product->ProductResellerPrice ?? $product->ProductPrice ?? 0;
                $changes[] = "price: {$op->productPrice} → {$restoredPrice}";
                if (!$dryRun) $op->productPrice = $restoredPrice;
            }

            if (!empty($changes)) {
                $order = Order::find($op->order_id);
                $invoiceID = $order ? $order->invoiceID : 'N/A';
                $this->line("  Order #{$invoiceID}: " . implode(', ', $changes));

                if (!$dryRun) $op->save();
                $restoredCount++;
            }
        }

        $this->info("  ✅ Restored {$restoredCount} order products");

        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  STEP 3: Recalculate Order Totals');
        $this->info('═══════════════════════════════════════');

        // Recalculate subTotal and profit for affected orders
        $affectedOrderIds = $duplicates->pluck('order_id')
            ->merge($broken->pluck('order_id'))
            ->unique();

        $recalcCount = 0;
        foreach ($affectedOrderIds as $orderId) {
            $order = Order::find($orderId);
            if (!$order) continue;

            $orderProducts = Orderproduct::where('order_id', $orderId)->get();
            $buy = $orderProducts->sum(function ($op) {
                return ($op->productPrice ?? 0) * ($op->quantity ?? 1);
            });

            // subTotal = buy + profit (selling total)
            // We can't recover the original profit, so keep existing if reasonable
            $currentSubTotal = $order->subTotal;
            $newSubTotal = $buy + $order->profit;

            if (abs($currentSubTotal - $newSubTotal) > 1) {
                $this->line("  Order #{$order->invoiceID}: subTotal {$currentSubTotal} → {$newSubTotal}");
                if (!$dryRun) {
                    $order->subTotal = $newSubTotal;
                    $order->save();
                }
                $recalcCount++;
            }
        }

        $this->info("  ✅ Recalculated {$recalcCount} order totals");

        $this->info('');
        $this->info('════════════════════════════════');
        $this->info('  REPAIR COMPLETE');
        $this->info('════════════════════════════════');
        if ($dryRun) {
            $this->warn('  Run without --dry-run to apply changes');
        }

        return 0;
    }
}
