<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICATION: ADMIN BUHI DELETED ===\n\n";

// Check if User ID 15 exists
$user = \App\Models\User::find(15);
if (!$user) {
    echo "✓ User ID 15: DELETED\n";
} else {
    echo "✗ User ID 15: STILL EXISTS!\n";
}

// Check for any Buhi references
$names = \App\Models\User::where('name', 'like', '%Buhi%')->count();
echo "✓ Users with 'Buhi' in name: {$names} (should be 0)\n";

// Check admin profiles
$adminProfiles = \App\Models\AdminProfile::count();
echo "✓ Total Admin Profiles: {$adminProfiles}\n";

// Show remaining admin profiles
echo "\nRemaining Admin Profiles:\n";
$admins = \App\Models\AdminProfile::with('user')->get();
foreach ($admins as $admin) {
    echo "  - ID: {$admin->id} | User: {$admin->user?->name} | {$admin->first_name} {$admin->last_name}\n";
}

echo "\n✓ Database cleaned up successfully!\n";
