<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->boolean('is_absent')->default(false)->after('status');
        });

        DB::table('leave_requests')
            ->where('status', 'approved')
            ->whereIn('employee_profile_id', function ($query) {
                $query->select('id')
                    ->from('employee_profiles')
                    ->whereIn('status', ['New Hire', 'New Hired']);
            })
            ->update(['is_absent' => true]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('is_absent');
        });
    }
};