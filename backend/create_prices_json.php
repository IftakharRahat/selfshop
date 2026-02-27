<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::where('ProductName', 'like', '%Bird%')
    ->orWhere('ProductName', 'like', '%Car Toy%')
    ->get(['id', 'ProductName', 'ProductRegularPrice', 'ProductResellerPrice', 'selling_type']);

$data = [];
foreach ($products as $p) {
    $data[] = [
        'id' => $p->id,
        'name' => $p->ProductName,
        'type' => $p->selling_type,
        'regular' => $p->ProductRegularPrice,
        'reseller' => $p->ProductResellerPrice,
    ];
}
file_put_contents(__DIR__.'/prices_json.txt', json_encode($data, JSON_PRETTY_PRINT));
echo "Done saving json\n";
