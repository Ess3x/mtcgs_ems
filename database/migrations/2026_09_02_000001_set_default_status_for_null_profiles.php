<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all null or empty status values to 'New Hire' as default
        DB::table('employee_profiles')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'New Hire']);

        DB::table('finance_profiles')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'New Hire']);
    }

    public function down(): void
    {
        // Revert to null if needed
        DB::table('employee_profiles')
            ->where('status', 'New Hire')
            ->update(['status' => null]);

        DB::table('finance_profiles')
            ->where('status', 'New Hire')
            ->update(['status' => null]);
    }
};
