<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;

echo "Final Verification Report:\n";
echo "Total Categories: " . Category::count() . "\n";
echo "Categories with front_status 0: " . Category::where('front_status', 0)->count() . "\n";
echo "Categories with status Active: " . Category::where('status', 'Active')->count() . "\n";

echo "Total Products: " . Product::count() . "\n";
echo "Featured Products (frature 0): " . Product::where('frature', '0')->count() . "\n";
echo "Top Rated Products (top_rated 1): " . Product::where('top_rated', '1')->count() . "\n";
echo "New Arrivals (show_new_product On): " . Product::where('show_new_product', 'On')->count() . "\n";

$p = Product::first();
if ($p) {
    echo "\nSample Product ID: " . $p->id . "\n";
    echo "Status: " . $p->status . "\n";
    echo "frature: '" . $p->frature . "'\n";
    echo "top_rated: '" . $p->top_rated . "'\n";
    echo "show_new_product: '" . $p->show_new_product . "'\n";
    echo "vendor_id: '" . ($p->vendor_id ?? 'NULL') . "'\n";
}
echo "Done.\n";
