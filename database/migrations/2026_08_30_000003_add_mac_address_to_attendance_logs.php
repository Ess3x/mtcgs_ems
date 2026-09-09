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
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('device_mac_address')->nullable()->after('verification_method')->comment('MAC address of device used for this attendance');
            $table->unsignedBigInteger('device_id')->nullable()->after('device_mac_address')->comment('Foreign key to devices table');
            
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->index('device_mac_address');
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropIndex(['device_mac_address']);
            $table->dropIndex(['device_id']);
            $table->dropColumn(['device_mac_address', 'device_id']);
        });
    }
};
