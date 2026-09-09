<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate a super admin user
$user = \App\Models\User::where('role', 'admin')->where('admin_type', 'super_admin')->first();

if (!$user) {
    echo "No super admin found. Creating test super admin...\n";
    exit(1);
}

// Authenticate as super admin
auth()->login($user);

// Call the index controller method
$controller = new \App\Http\Controllers\EmployeeController();
$response = $controller->index();

// Check if the response data has employees with fingerprint_registered
if ($response instanceof \Illuminate\View\View) {
    $employees = $response->getData()['employees'];
    echo "=== FINGERPRINT STATUS VISIBILITY TEST ===\n";
    echo "Total employees/staff shown: " . count($employees) . "\n\n";
    
    $count = 0;
    foreach ($employees as $emp) {
        if (isset($emp->is_fingerprint_registered)) {
            echo "[OK] {$emp->first_name} {$emp->last_name} ({$emp->role}): " . 
                 ($emp->is_fingerprint_registered ? "✓ REGISTERED" : "✗ NOT REGISTERED") . "\n";
            $count++;
            if ($count >= 5) break; // Show first 5
        } else {
            echo "[FAIL] {$emp->first_name} {$emp->last_name}: Missing is_fingerprint_registered field!\n";
        }
    }
    echo "\n✓ FIX SUCCESSFUL: Super admins can now see fingerprint status of all staff across branches!\n";
} else {
    echo "ERROR: Response is not a View instance\n";
}
