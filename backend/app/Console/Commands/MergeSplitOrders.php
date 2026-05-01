<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeSplitOrders extends Command
{
    protected $signature = 'orders:merge-splits {--fix : Actually merge (without this flag, only shows what would be merged)}';
    protected $description = 'Find and merge split orders that were created from the same cart (same user, same phone, same date, within seconds)';

    public function handle()
    {
        $dryRun = !$this->option('fix');

        if ($dryRun) {
            $this->info("🔍 DRY RUN — showing what would be merged. Use --fix to actually merge.\n");
        } else {
            $this->warn("⚠️  LIVE MODE — will actually merge orders!\n");
            if (!$this->confirm('Are you sure you want to proceed?')) {
                return;
            }
        }

        // Find pending orders grouped by user_id + orderDate
        $candidates = DB::table('orders')
            ->select('orders.id', 'orders.invoiceID', 'orders.user_id', 'orders.orderDate',
                'orders.subTotal', 'orders.profit', 'orders.deliveryCharge', 'orders.paymentAmount',
                'orders.store_id', 'orders.status', 'orders.created_at',
                'customers.customerPhone', 'customers.customerName')
            ->leftJoin('customers', 'orders.id', '=', 'customers.order_id')
            ->where('orders.status', 'Pending')
            ->whereNotNull('orders.user_id')
            ->orderBy('orders.user_id')
            ->orderBy('orders.created_at')
            ->get();

        // Group by user_id + customerPhone + orderDate
        $groups = $candidates->groupBy(function ($order) {
            return $order->user_id . '|' . $order->customerPhone . '|' . $order->orderDate;
        });

        $mergeCount = 0;

        foreach ($groups as $key => $orders) {
            if ($orders->count() <= 1) continue;

            // Check if orders were created within 60 seconds of each other
            $firstCreated = strtotime($orders->first()->created_at);
            $lastCreated = strtotime($orders->last()->created_at);
            if (abs($lastCreated - $firstCreated) > 60) continue;

            $mergeCount++;
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("GROUP #{$mergeCount}: {$orders->first()->customerName} ({$orders->first()->customerPhone})");
            $this->info("Date: {$orders->first()->orderDate} | User ID: {$orders->first()->user_id}");
            $this->info("Found {$orders->count()} split orders:");

            $keepOrder = $orders->first(); // Keep the first order
            $mergeOrders = $orders->slice(1); // Merge the rest into it

            foreach ($orders as $o) {
                $productCount = Orderproduct::where('order_id', $o->id)->count();
                $products = Orderproduct::where('order_id', $o->id)->pluck('productName')->implode(', ');
                $marker = $o->id === $keepOrder->id ? ' ← KEEP' : ' ← MERGE INTO FIRST';
                $this->line("  #{$o->id} ({$o->invoiceID}) | SubTotal: {$o->subTotal} | Delivery: {$o->deliveryCharge} | Products({$productCount}): {$products}{$marker}");
            }

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    // 1. Move all products from merge orders to keep order
                    $allBuy = 0;
                    $allSell = 0;
                    $allBonus = 0;
                    $allProducts = collect();

                    foreach ($orders as $o) {
                        $orderProducts = Orderproduct::where('order_id', $o->id)->get();
                        foreach ($orderProducts as $op) {
                            $allProducts->push($op);
                            $costPrice = (float) $op->productPrice;
                            $sellingPrice = (float) ($op->selling_price ?? $costPrice);
                            $allBuy += $costPrice * $op->quantity;
                            $allSell += $sellingPrice * $op->quantity;
                            $prod = Product::find($op->product_id);
                            $allBonus += $prod->reseller_bonus ?? 0;
                        }
                    }

                    // 2. Update products from duplicate orders to point to keep order
                    foreach ($mergeOrders as $mo) {
                        Orderproduct::where('order_id', $mo->id)->update(['order_id' => $keepOrder->id]);
                    }

                    // 3. Update keep order totals
                    $mainOrder = Order::find($keepOrder->id);
                    $shopCount = $allProducts->map(function ($op) {
                        $prod = Product::find($op->product_id);
                        return $prod ? ($prod->shop_id ?? 1) : 1;
                    })->unique()->count();

                    $mainOrder->subTotal = $allSell;
                    $mainOrder->profit = $allSell - $allBuy;
                    $mainOrder->order_bonus = $allBonus;
                    $mainOrder->shop_count = $shopCount;
                    // Delivery charge: keep as-is (it was the same per-shop charge)
                    // For old orders, deliveryCharge was the single zone charge (not multiplied)
                    // We multiply it now: deliveryCharge × shopCount
                    $mainOrder->deliveryCharge = $keepOrder->deliveryCharge * $shopCount;
                    $mainOrder->store_id = $shopCount === 1 ? $mainOrder->store_id : 1;
                    $mainOrder->save();

                    // 4. Delete duplicate orders + their customer records + comments
                    foreach ($mergeOrders as $mo) {
                        Customer::where('order_id', $mo->id)->delete();
                        Comment::where('order_id', $mo->id)->delete();
                        Order::where('id', $mo->id)->delete();
                    }

                    DB::commit();
                    $this->info("  ✅ Merged! Kept #{$keepOrder->id}, deleted " . $mergeOrders->pluck('id')->implode(', '));
                    $this->info("  New totals: SubTotal={$mainOrder->subTotal}, Profit={$mainOrder->profit}, Delivery={$mainOrder->deliveryCharge}, ShopCount={$shopCount}");
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->error("  ❌ Failed to merge: " . $e->getMessage());
                }
            }

            $this->line('');
        }

        if ($mergeCount === 0) {
            $this->info("✅ No split orders found! Everything looks clean.");
        } else {
            $this->info("Found {$mergeCount} group(s) of split orders.");
            if ($dryRun) {
                $this->warn("Run with --fix to actually merge them: php artisan orders:merge-splits --fix");
            }
        }
    }
}
