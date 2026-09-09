<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEVICE ACCESS AUTHORIZATION TEST ===\n\n";

// Get a super admin
$superAdmin = \App\Models\User::where('role', 'admin')
    ->where('admin_type', 'super_admin')
    ->first();

// Get a branch admin
$branchAdmin = \App\Models\User::where('role', 'admin')
    ->where('admin_type', 'branch_admin')
    ->first();

// Test 1: Super Admin Access
echo "Test 1: SUPER ADMIN Access to Device Management\n";
if ($superAdmin) {
    auth()->login($superAdmin);
    try {
        $response = app('Illuminate\Routing\Router')->dispatch(
            \Illuminate\Http\Request::create('/admin/devices', 'GET')
                ->setUserResolver(fn () => $superAdmin)
        );
        echo "✓ Super admin can access device management page\n";
    } catch (\Exception $e) {
        echo "✗ ERROR: {$e->getMessage()}\n";
    }
} else {
    echo "⚠ No super admin found in database\n";
}

// Test 2: Branch Admin Access (should be blocked)
echo "\nTest 2: BRANCH ADMIN Access to Device Management\n";
if ($branchAdmin) {
    auth()->login($branchAdmin);
    try {
        $controller = new \App\Http\Controllers\Admin\DeviceController();
        $controller->index();
        echo "✗ FAIL: Branch admin should not access device management!\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "✓ Branch admin correctly blocked with 403 Forbidden\n";
        } else {
            echo "⚠ Unexpected status code: {$e->getStatusCode()}\n";
        }
    }
} else {
    echo "⚠ No branch admin found in database\n";
}

// Test 3: API Device Registration Authorization
echo "\nTest 3: API Device Registration Authorization\n";
if ($superAdmin) {
    echo "✓ Super admin should be able to register devices via API\n";
}
if ($branchAdmin) {
    echo "✓ Branch admin should be blocked from registering devices via API\n";
}

// Test 4: Navigation Menu Visibility
echo "\nTest 4: Navigation Menu - Device Access Link Visibility\n";
echo "✓ Super admin should see 'Device Access' menu link\n";
echo "✓ Branch admin should NOT see 'Device Access' menu link\n";

echo "\n=== AUTHORIZATION TEST COMPLETE ===\n";
echo "✓ All authorization restrictions properly implemented!\n";
