<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting product aggregation refresh...\n";

Product::whereHas('varients')->chunk(100, function ($products) {
    foreach ($products as $product) {
        $variants = $product->varients()->with('sizes')->get();
        if ($variants->isEmpty()) {
            continue;
        }

        $totalQty = 0;
        $minPrice = null;

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
                    $sPrice = (float) ($size->price ?: 0);
                    if ($sPrice > 0 && ($minPrice === null || $sPrice < $minPrice)) {
                        $minPrice = $sPrice;
                    }
                }
            }
        }

        $product->qty = $totalQty;
        if ($minPrice !== null && $minPrice > 0) {
            $product->ProductResellerPrice = $minPrice;
        }
        $product->save();
        echo "Refreshed Product ID: {$product->id} - Qty: {$totalQty}, Price: {$minPrice}\n";
    }
});

echo "Finished product aggregation refresh.\n";
