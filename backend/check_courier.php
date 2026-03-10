<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check how profit is calculated for product Ata
$product = DB::table('products')->where('id', 366)->first();
echo "Product Ata (#366):\n";
echo "  ProductResellerPrice (buy): " . ($product->ProductResellerPrice ?? 'NULL') . "\n";
echo "  selling_price: " . ($product->selling_price ?? 'NULL') . "\n";
echo "  reseller_bonus: " . ($product->reseller_bonus ?? 'NULL') . "\n";

// Check order 384
$order = DB::table('orders')->where('id', 384)->first();
echo "\nOrder #384:\n";
echo "  profit: " . ($order->profit ?? 'NULL') . "\n";
echo "  subTotal: " . ($order->subTotal ?? 'NULL') . "\n";
echo "  order_bonus: " . ($order->order_bonus ?? 'NULL') . "\n";

// Calculate actual profit
// profit = sellPrice - buyPrice  
$ops = DB::table('orderproducts')->where('order_id', 384)->get();
$sellTotal = 0;
$buyTotal = 0;
$bonus = 0;
foreach ($ops as $op) {
    $p = DB::table('products')->where('id', $op->product_id)->first();
    $sell = $op->productPrice * $op->quantity;
    $buy = ($p->ProductResellerPrice ?? 0) * $op->quantity;
    $b = $p->reseller_bonus ?? 0;
    $sellTotal += $sell;
    $buyTotal += $buy;
    $bonus += $b;
    echo "  Product: {$op->productName} | sell: {$sell} | buy: {$buy} | bonus: {$b}\n";
}
$profit = $sellTotal - $buyTotal;
echo "\nCalculated: sellTotal={$sellTotal} buyTotal={$buyTotal} profit={$profit} bonus={$bonus}\n";

// Fix all SSLCommerz orders
$ids = [384, 385, 386, 387];
foreach ($ids as $id) {
    $orderOps = DB::table('orderproducts')->where('order_id', $id)->get();
    $s = 0; $b = 0; $bn = 0;
    foreach ($orderOps as $op) {
        $p = DB::table('products')->where('id', $op->product_id)->first();
        $s += $op->productPrice * $op->quantity;
        $b += ($p->ProductResellerPrice ?? 0) * $op->quantity;
        $bn += $p->reseller_bonus ?? 0;
    }
    $pr = $s - $b;
    DB::table('orders')->where('id', $id)->update([
        'profit' => $pr,
        'subTotal' => $s,
        'order_bonus' => $bn,
    ]);
    echo "Order #{$id}: subTotal={$s}, profit={$pr}, bonus={$bn}\n";
}

echo "\nDone!\n";
