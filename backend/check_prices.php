<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::where('ProductName', 'like', '%Bird%')
    ->orWhere('ProductName', 'like', '%Car Toy%')
    ->get(['id', 'ProductName', 'ProductRegularPrice', 'ProductResellerPrice', 'selling_type']);

foreach ($products as $p) {
    echo "ID: {$p->id} | Name: {$p->ProductName} | Type: {$p->selling_type} | Regular: {$p->ProductRegularPrice} | Reseller: {$p->ProductResellerPrice}\n";
}
