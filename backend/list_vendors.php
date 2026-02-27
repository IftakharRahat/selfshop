<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;

$vendors = Vendor::with('user')->get();
foreach ($vendors as $v) {
    $email = $v->user ? $v->user->email : 'N/A';
    echo "Vendor: {$v->company_name} | User: {$email} | Status: {$v->status}\n";
}
?>
