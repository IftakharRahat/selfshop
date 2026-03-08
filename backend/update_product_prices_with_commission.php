<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Services\VendorCommissionService;

try {
    $products = Product::whereNotNull('vendor_id')->get();
    $commissionService = app(VendorCommissionService::class);
    $count = 0;

    echo "Updating " . $products->count() . " vendor products...\n";

    foreach ($products as $product) {
        $displayPrice = $commissionService->getStorefrontPrice(
            (float) $product->ProductResellerPrice,
            (int) $product->vendor_id,
            (int) $product->category_id
        );
        
        $product->ProductRegularPrice = $displayPrice;
        $product->ProductSalePrice = $displayPrice;
        $product->save();
        $count++;
    }

    echo "Successfully updated $count product prices.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
