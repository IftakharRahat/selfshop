<?php
/**
 * Force visibility of categories and products on the frontend.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;

echo "Enabling all Categories for frontend...\n";
Category::query()->update([
    'status' => 'Active',
    'front_status' => 0, // In this app, 0 seems to be the "Visible" state based on the controller
]);

echo "Enabling all Products for frontend collections (Featured, Big Selling, New)...\n";
Product::query()->update([
    'status' => 'Active',
    'frature' => '0',       // 0 = Featured in this controller
    'top_rated' => '1',    // 1 = Big Selling
    'show_new_product' => 'On', // On = New Products
    'hot_list' => 'On',
    'ready_bost' => 'On',
    'profitable' => 'On',
    'limited' => 'On',
    'summer' => 'On',
]);

echo "Product Count: " . Product::count() . "\n";
echo "Category Count: " . Category::count() . "\n";
echo "Visible Categories: " . Category::where('status', 'Active')->count() . "\n";
echo "Done.\n";
