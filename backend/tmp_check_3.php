<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$cols = DB::select('SHOW COLUMNS FROM products');
$names = array_map(fn($c) => $c->Field, $cols);
file_put_contents('cols.txt', implode("\n", $names));
