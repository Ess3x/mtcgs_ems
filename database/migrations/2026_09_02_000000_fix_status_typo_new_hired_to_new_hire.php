<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix the 'New Hired' typo to 'New Hire' in employee_profiles
        if (DB::getDriverName() !== 'sqlite') {
            DB::table('employee_profiles')
                ->where('status', 'New Hired')
                ->update(['status' => 'New Hire']);

            DB::table('finance_profiles')
                ->where('status', 'New Hired')
                ->update(['status' => 'New Hire']);
        } else {
            // For SQLite
            DB::statement("UPDATE employee_profiles SET status = 'New Hire' WHERE status = 'New Hired'");
            DB::statement("UPDATE finance_profiles SET status = 'New Hire' WHERE status = 'New Hired'");
        }
    }

    public function down(): void
    {
        // Revert the fix if needed
        if (DB::getDriverName() !== 'sqlite') {
            DB::table('employee_profiles')
                ->where('status', 'New Hire')
                ->where('status', '!=', 'Regular')
                ->where('status', '!=', '1-2 Years in Service')
                ->where('status', '!=', '3+ Years of Service')
                ->update(['status' => 'New Hired']);

            DB::table('finance_profiles')
                ->where('status', 'New Hire')
                ->where('status', '!=', 'Regular')
                ->where('status', '!=', '1-2 Years in Service')
                ->where('status', '!=', '3+ Years of Service')
                ->update(['status' => 'New Hired']);
        }
    }
};
