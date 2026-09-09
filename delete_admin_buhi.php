<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DELETING BROKEN ADMIN BUHI RECORD ===\n\n";

$user = \App\Models\User::find(15);

if ($user) {
    echo "DELETING:\n";
    echo "  User ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Role: {$user->role}\n";
    echo "  Reason: Broken reference (admin_profile ID {$user->profile_id} does not exist)\n\n";
    
    // Delete the user
    $user->delete();
    
    echo "✓ Record successfully deleted!\n\n";
    
    // Verify deletion
    $check = \App\Models\User::find(15);
    if (!$check) {
        echo "✓ Verified: User ID 15 no longer exists in database\n";
    } else {
        echo "✗ ERROR: User ID 15 still exists!\n";
    }
} else {
    echo "User ID 15 not found (already deleted?)\n";
}
