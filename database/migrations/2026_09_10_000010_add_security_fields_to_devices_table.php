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
            if (!Schema::hasColumn('devices', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('mac_address')->unique()->comment('Fingerprint scanner serial number');
            }

            if (!Schema::hasColumn('devices', 'allowed_mac_addresses')) {
                $table->json('allowed_mac_addresses')->nullable()->after('serial_number')->comment('Approved MAC addresses for this device');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'allowed_mac_addresses')) {
                $table->dropColumn('allowed_mac_addresses');
            }

            if (Schema::hasColumn('devices', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
        });
    }
};
