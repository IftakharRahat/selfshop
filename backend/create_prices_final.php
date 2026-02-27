<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::where('ProductName', 'like', '%Bird%')
    ->orWhere('ProductName', 'like', '%Car Toy%')
    ->get();

$out = "";
foreach($products as $p) {
    $out .= "ID: {$p->id} | Name: {$p->ProductName} | Reg: {$p->ProductRegularPrice} | Res: {$p->ProductResellerPrice}\n";
}
file_put_contents('prices_final.txt', $out);
echo "Prices saved to prices_final.txt\n";
