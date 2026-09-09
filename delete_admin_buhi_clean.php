<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CLEANING UP ADMIN BUHI RECORD (WITH DEPENDENCIES) ===\n\n";

$user = \App\Models\User::find(15);

if ($user) {
    echo "FOUND USER:\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n\n";
    
    // Check for related records
    $calendarEvents = \App\Models\CalendarEvent::where('created_by', 15)->count();
    echo "Checking dependencies:\n";
    echo "  Calendar Events: {$calendarEvents}\n";
    
    if ($calendarEvents > 0) {
        echo "\nDeleting {$calendarEvents} calendar event(s)...\n";
        \App\Models\CalendarEvent::where('created_by', 15)->delete();
        echo "✓ Calendar events deleted\n";
    }
    
    // Now delete the user
    echo "\nDeleting user record...\n";
    $user->delete();
    echo "✓ User deleted\n\n";
    
    // Verify
    $check = \App\Models\User::find(15);
    if (!$check) {
        echo "✓ VERIFIED: Admin Buhi (User ID 15) successfully removed from database!\n";
    }
} else {
    echo "User ID 15 not found (already deleted?)\n";
}
