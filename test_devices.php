<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$devices = DB::table('devices')->get();

echo "=== REGISTERED DEVICES ===\n";
if ($devices->count() > 0) {
    foreach ($devices as $device) {
        echo "ID: {$device->id} | MAC: {$device->mac_address} | Name: {$device->device_name}\n";
    }
} else {
    echo "No devices registered yet.\n";
}
echo "\n";
