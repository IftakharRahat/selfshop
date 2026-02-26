<?php
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Manually log in the admin
$admin = Admin::where('email', 'md.muraiem@gmail.com')->first();
if (!$admin) {
    echo "Admin user not found!\n";
    exit(1);
}

Auth::guard('admin')->login($admin);

// Mock a request to the dashboard
$request = Illuminate\Http\Request::create('/admin/dashboard', 'GET');
$request->setLaravelSession($app['session']->driver());

$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 500) {
    echo "ERROR: Dashboard returned 500\n";
    // Check for exception in response if possible or just rely on logs
} else {
    echo "SUCCESS: Dashboard loaded with status " . $response->getStatusCode() . "\n";
}
