<?php
// Script to refresh aggregation for product 21
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::find(21);
if(!$product) {
    die("Product 21 not found\n");
}

echo "Working on Product #21 ({$product->ProductName})\n";

$totalQty = 0;
$minPrice = null;

$variants = $product->varients()->with(['sizes.bulkPrices'])->get();
echo "Found " . $variants->count() . " variants\n";

foreach ($variants as $variant) {
    if ($variant->sizes->isEmpty()) {
        $totalQty += (int) $variant->qty;
        $vPrice = (float) ($variant->price ?: 0);
        if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
            $minPrice = $vPrice;
        }
    } else {
        foreach ($variant->sizes as $size) {
            $totalQty += (int) $size->qty;
            
            $pricesToCompare = [];
            $sPrice = (float) ($size->price ?: 0);
            if ($sPrice > 0) {
                $pricesToCompare[] = $sPrice;
            }
            
            if ($size->bulkPrices && $size->bulkPrices->isNotEmpty()) {
                foreach ($size->bulkPrices as $bp) {
                    $bpPrice = (float) ($bp->bulk_price ?: 0);
                    if ($bpPrice > 0) {
                        $pricesToCompare[] = $bpPrice;
                    }
                }
            }

            if (!empty($pricesToCompare)) {
                $bestSizePrice = min($pricesToCompare);
                if ($minPrice === null || $bestSizePrice < $minPrice) {
                    $minPrice = $bestSizePrice;
                }
            }
        }
    }
}

$product->qty = $totalQty;
if ($minPrice !== null && $minPrice > 0) {
    $product->ProductResellerPrice = $minPrice;
}
$product->save();

echo "Finished! Updated Product #21: Qty={$product->qty}, Price={$product->ProductResellerPrice}\n";
