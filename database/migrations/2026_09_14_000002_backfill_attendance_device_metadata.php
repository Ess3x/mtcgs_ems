<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "UPDATE attendance_logs AS attendance
             INNER JOIN devices AS device ON device.id = attendance.device_id
             SET attendance.device_serial_number = device.serial_number,
                 attendance.location = device.location
             WHERE attendance.device_id IS NOT NULL
               AND (attendance.device_serial_number IS NULL OR attendance.location IS NULL)"
        );
    }

    public function down(): void
    {
        // Historical device metadata should not be removed on rollback.
    }
};
