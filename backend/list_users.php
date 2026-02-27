<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::all(['email', 'status', 'expire_date']);
foreach ($users as $user) {
    echo "Email: {$user->email} | Status: {$user->status} | Expire: {$user->expire_date}\n";
}
?>
