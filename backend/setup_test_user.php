<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'supplier@example.com';
$user = User::where('email', $email)->first();
if (!$user) {
    echo "Creating user\n";
    $user = new User();
    $user->email = $email;
    $user->name = 'Supplier Test';
    $user->my_referral_code = 'TEST001';
    $user->refer_by = 'ADMIN';
    $user->phone = '01700000000';
}

$user->password = Hash::make('password');
$user->status = 'Active';
$user->expire_date = '2030-01-01';
$user->save();

echo "User {$email} is ready with password 'password'\n";
?>
