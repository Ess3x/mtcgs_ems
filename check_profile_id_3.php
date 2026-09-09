<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING ADMIN_PROFILE ID 3 ===\n\n";

$profile = \App\Models\AdminProfile::find(3);

if ($profile) {
    echo "ADMIN_PROFILE ID 3 EXISTS:\n";
    echo "  Name: {$profile->first_name} {$profile->last_name}\n";
    echo "  Position: {$profile->position}\n";
    echo "  User ID: {$profile->user_id}\n";
    echo "  Employee #: {$profile->employee_number}\n";
    echo "  Created: {$profile->created_at}\n";
} else {
    echo "ADMIN_PROFILE ID 3 DOES NOT EXIST\n";
    echo "\nThis is a DATA INTEGRITY ISSUE:\n";
    echo "- User ID 15 references admin_profile ID 3\n";
    echo "- But admin_profile ID 3 doesn't exist!\n\n";
    
    // Check what admin profiles DO exist
    echo "Existing Admin Profiles:\n";
    $allAdmins = \App\Models\AdminProfile::all();
    foreach ($allAdmins as $admin) {
        echo "  ID: {$admin->id} | User: {$admin->user_id} | Name: {$admin->first_name} {$admin->last_name}\n";
    }
}
