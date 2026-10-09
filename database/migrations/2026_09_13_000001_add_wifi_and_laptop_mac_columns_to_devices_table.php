<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (!Schema::hasColumn('devices', 'wifi_mac_address')) {
                $table->string('wifi_mac_address')->nullable()->after('serial_number')->comment('Wi-Fi MAC address of the device');
            }

            if (!Schema::hasColumn('devices', 'laptop_mac_address')) {
                $table->string('laptop_mac_address')->nullable()->after('wifi_mac_address')->comment('Laptop MAC address of the device');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'laptop_mac_address')) {
                $table->dropColumn('laptop_mac_address');
            }

            if (Schema::hasColumn('devices', 'wifi_mac_address')) {
                $table->dropColumn('wifi_mac_address');
            }
        });
    }
};
