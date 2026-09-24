<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_logs', 'device_serial_number')) {
                $table->string('device_serial_number')->nullable()->after('device_id');
            }

            if (!Schema::hasColumn('attendance_logs', 'location')) {
                $table->string('location')->nullable()->after('device_serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'location')) {
                $table->dropColumn('location');
            }

            if (Schema::hasColumn('attendance_logs', 'device_serial_number')) {
                $table->dropColumn('device_serial_number');
            }
        });
    }
};
