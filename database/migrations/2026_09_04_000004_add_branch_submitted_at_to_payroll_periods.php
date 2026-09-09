<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_periods', 'branch_submitted_at')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->timestamp('branch_submitted_at')->nullable()->after('hr_approved_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_periods', 'branch_submitted_at')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->dropColumn('branch_submitted_at');
            });
        }
    }
};
