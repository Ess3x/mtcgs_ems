<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "UPDATE attendance_logs AS attendance
             LEFT JOIN devices AS device_by_id ON device_by_id.id = attendance.device_id
             LEFT JOIN devices AS device_by_mac ON device_by_mac.status = 'active' AND (
                 REPLACE(REPLACE(REPLACE(UPPER(COALESCE(device_by_mac.mac_address, '')), ':', ''), '-', ''), ' ', '') =
                 REPLACE(REPLACE(REPLACE(UPPER(COALESCE(attendance.device_mac_address, '')), ':', ''), '-', ''), ' ', '')
                 OR REPLACE(REPLACE(REPLACE(UPPER(COALESCE(device_by_mac.wifi_mac_address, '')), ':', ''), '-', ''), ' ', '') =
                 REPLACE(REPLACE(REPLACE(UPPER(COALESCE(attendance.device_mac_address, '')), ':', ''), '-', ''), ' ', '')
             )
             SET attendance.device_id = COALESCE(attendance.device_id, device_by_mac.id),
                 attendance.device_mac_address = COALESCE(NULLIF(attendance.device_mac_address, ''), device_by_mac.mac_address),
                 attendance.device_serial_number = COALESCE(NULLIF(attendance.device_serial_number, ''), device_by_id.serial_number, device_by_mac.serial_number),
                 attendance.location = COALESCE(NULLIF(TRIM(attendance.location), ''), device_by_id.location, device_by_mac.location)
             WHERE attendance.device_id IS NULL
                OR attendance.device_mac_address IS NULL
                OR TRIM(COALESCE(attendance.location, '')) = ''
                OR TRIM(COALESCE(attendance.device_serial_number, '')) = ''"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for historical cleanup data.
    }
};
