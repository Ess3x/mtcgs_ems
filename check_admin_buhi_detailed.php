<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DETAILED ADMIN BUHI DATABASE CHECK ===\n\n";

// Check the user with ID 15
$user = \App\Models\User::find(15);

if ($user) {
    echo "USER RECORD (ID: 15):\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Role: {$user->role}\n";
    echo "  Admin Type: {$user->admin_type}\n";
    echo "  Profile ID: {$user->profile_id}\n";
    echo "  Profile Type: {$user->profile_type}\n";
    echo "  Active: " . ($user->is_active ? "YES" : "NO") . "\n\n";

    // Get the admin profile
    $adminProfile = $user->getAdminProfile();
    if ($adminProfile) {
        echo "ADMIN PROFILE:\n";
        echo "  ID: {$adminProfile->id}\n";
        echo "  Name: {$adminProfile->first_name} {$adminProfile->last_name}\n";
        echo "  Position: {$adminProfile->position}\n";
        echo "  Employee #: {$adminProfile->employee_number}\n";
        echo "  Fingerprint Registered: " . ($adminProfile->is_fingerprint_registered ? "YES" : "NO") . "\n";
        echo "  Branch ID: {$adminProfile->branch_id}\n";
        echo "  Created: {$adminProfile->created_at}\n\n";
    } else {
        echo "NO ADMIN PROFILE FOUND!\n\n";
    }

    // Check if there are ANY other admin_profiles with the same user_id
    $otherProfiles = \App\Models\AdminProfile::where('user_id', 15)->get();
    echo "Total AdminProfile records linked to User ID 15: " . $otherProfiles->count() . "\n";
    if ($otherProfiles->count() > 0) {
        foreach ($otherProfiles as $idx => $profile) {
            echo "  [{$idx}] Admin Profile ID: {$profile->id} - {$profile->first_name} {$profile->last_name}\n";
        }
    }
    
    // Check audit logs for this user
    echo "\n--- AUDIT LOG (last 5 entries) ---\n";
    $logs = \App\Models\AuditLog::where('user_id', 15)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($logs->count() > 0) {
        foreach ($logs as $log) {
            echo "  {$log->created_at}: {$log->action} - {$log->description}\n";
        }
    } else {
        echo "  No audit logs found\n";
    }

} else {
    echo "User ID 15 not found!\n";
}
