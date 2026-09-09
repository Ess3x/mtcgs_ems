<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            foreach (['corrected_pm_in', 'corrected_time_out'] as $column) {
                if (!Schema::hasColumn('attendance_logs', $column)) {
                    $table->dateTime($column)->nullable()->after('corrected_time_in');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            foreach (['corrected_pm_in', 'corrected_time_out'] as $column) {
                if (Schema::hasColumn('attendance_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
