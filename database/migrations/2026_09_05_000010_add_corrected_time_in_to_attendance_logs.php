<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendance_logs', 'corrected_time_in')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->dateTime('corrected_time_in')->nullable()->after('override_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_logs', 'corrected_time_in')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->dropColumn('corrected_time_in');
            });
        }
    }
};
