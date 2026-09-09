<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SEARCHING FOR ADMIN BUHI ===\n\n";

// Search in admin_profiles
$adminBuhis = \App\Models\AdminProfile::where('first_name', 'like', '%Buhi%')
    ->orWhere('last_name', 'like', '%Buhi%')
    ->with('user')
    ->get();

if ($adminBuhis->count() > 0) {
    echo "Found " . $adminBuhis->count() . " admin Buhi record(s):\n\n";
    foreach ($adminBuhis as $admin) {
        echo "Admin ID: {$admin->id}\n";
        echo "  Name: {$admin->first_name} {$admin->last_name}\n";
        echo "  Employee #: {$admin->employee_number}\n";
        echo "  Position: {$admin->position}\n";
        echo "  Email: {$admin->user?->email}\n";
        echo "  User ID: {$admin->user?->id}\n";
        echo "  User Role: {$admin->user?->role}\n";
        echo "  User Admin Type: {$admin->user?->admin_type}\n";
        echo "  Created: {$admin->created_at}\n";
        echo "  Updated: {$admin->updated_at}\n";
        echo "---\n";
    }
} else {
    echo "No admin Buhi found!\n";
}

// Also search in users table
echo "\nSearching in users table:\n";
$userBuhis = \App\Models\User::where('name', 'like', '%Buhi%')
    ->where('role', 'admin')
    ->get();

if ($userBuhis->count() > 0) {
    echo "Found " . $userBuhis->count() . " user(s) with Buhi in name:\n\n";
    foreach ($userBuhis as $user) {
        echo "User ID: {$user->id}\n";
        echo "  Name: {$user->name}\n";
        echo "  Email: {$user->email}\n";
        echo "  Role: {$user->role}\n";
        echo "  Admin Type: {$user->admin_type}\n";
        echo "  Active: " . ($user->is_active ? "YES" : "NO") . "\n";
        echo "  Created: {$user->created_at}\n";
        echo "---\n";
    }
}
