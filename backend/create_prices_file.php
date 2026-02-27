<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::where('ProductName', 'like', '%Bird%')->orWhere('ProductName', 'like', '%Car Toy%')->get();
$output = "";
foreach($products as $p) {
    $output .= "ID: {$p->id} | Name: {$p->ProductName} | Type: {$p->selling_type} | Reg: {$p->ProductRegularPrice} | Res: {$p->ProductResellerPrice}\n";
}
file_put_contents('prices_output.txt', $output);
echo "Done\n";
