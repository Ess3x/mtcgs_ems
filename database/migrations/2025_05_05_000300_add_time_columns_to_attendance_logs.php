<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // Rename existing columns
            if (Schema::hasColumn('attendance_logs', 'time_in')) {
                $table->renameColumn('time_in', 'am_in');
            }
            if (Schema::hasColumn('attendance_logs', 'time_out')) {
                $table->renameColumn('time_out', 'pm_out');
            }
            
            // Add new time columns
            if (!Schema::hasColumn('attendance_logs', 'am_out')) {
                $table->datetime('am_out')->nullable()->after('am_in');
            }
            if (!Schema::hasColumn('attendance_logs', 'pm_in')) {
                $table->datetime('pm_in')->nullable()->after('am_out');
            }
            if (!Schema::hasColumn('attendance_logs', 'overtime_hours')) {
                $table->decimal('overtime_hours', 5, 2)->default(0)->after('late_minutes');
            }
        });
    }

    public function down()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->renameColumn('am_in', 'time_in');
            $table->renameColumn('pm_out', 'time_out');
            $table->dropColumn(['am_out', 'pm_in', 'overtime_hours']);
        });
    }
};
