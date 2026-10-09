<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('shifts')) {
            DB::table('shifts')->where('start_time', '07:00:00')->update(['start_time' => '08:00:00']);
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('attendance_logs')
            && \Illuminate\Support\Facades\Schema::hasTable('employee_profiles')
            && \Illuminate\Support\Facades\Schema::hasTable('shifts')) {
            $eightAmShiftIds = DB::table('shifts')->where('start_time', '08:00:00')->select('id');

            DB::table('attendance_logs')
                ->whereIn('employee_profile_id', DB::table('employee_profiles')
                    ->where(function ($query) use ($eightAmShiftIds) {
                        $query->whereNull('shift_id')->orWhereIn('shift_id', $eightAmShiftIds);
                    })
                    ->select('id'))
                ->whereNotNull('am_in')
                ->whereTime('am_in', '<=', '08:01:00')
                ->where(function ($query) {
                    $query->where('status', 'late')->orWhere('late_minutes', '>', 0);
                })
                ->update(['status' => 'present', 'late_minutes' => 0]);
        }
    }

    public function down(): void
    {
    }
};