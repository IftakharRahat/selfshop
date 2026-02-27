<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Database\Seeders\CategorySeeder;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;

$log = "";
try {
    $log .= "Running CategorySeeder...\n";
    (new CategorySeeder())->run();
    $log .= "CategorySeeder Success!\n";

    $log .= "Running BrandSeeder...\n";
    (new BrandSeeder())->run();
    $log .= "BrandSeeder Success!\n";

    $log .= "Running ProductSeeder...\n";
    (new ProductSeeder())->run();
    $log .= "ProductSeeder Success!\n";
} catch (\Exception $e) {
    $log .= "ERROR: " . $e->getMessage() . "\n";
    $log .= "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $log .= $e->getTraceAsString() . "\n";
}
file_put_contents(__DIR__ . '/debug_seed.log', $log);
echo "Done. Check debug_seed.log\n";
