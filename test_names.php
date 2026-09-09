<?php

require_once 'vendor/autoload.php';

use App\Models\EmployeeProfile;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Checking employee profile names...\n";

// Test ghost employee
$profile1 = EmployeeProfile::where('employee_number', 'EMP999')->first();
if ($profile1) {
    echo "EMP999 (Ghost Employee):\n";
    echo "First Name: " . $profile1->first_name . "\n";
    echo "Last Name: " . $profile1->last_name . "\n";
    echo "Full: " . $profile1->first_name . " " . $profile1->last_name . "\n";
}

// Test mark obero
$profile2 = EmployeeProfile::where('employee_number', 'EMP888')->first();
if ($profile2) {
    echo "\nEMP888 (Mark Obero):\n";
    echo "First Name: " . $profile2->first_name . "\n";
    echo "Last Name: " . $profile2->last_name . "\n";
    echo "Full: " . $profile2->first_name . " " . $profile2->last_name . "\n";
}