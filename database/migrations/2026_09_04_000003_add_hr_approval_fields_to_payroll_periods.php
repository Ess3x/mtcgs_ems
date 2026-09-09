<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'hr_approved_by')) {
                $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_approval_stage');
            }
            if (!Schema::hasColumn('payroll_periods', 'hr_approved_at')) {
                $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
            }
            if (!Schema::hasColumn('payroll_periods', 'branch_submitted_at')) {
                $table->timestamp('branch_submitted_at')->nullable()->after('hr_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'hr_approved_by')) {
                $table->dropForeign(['hr_approved_by']);
                $table->dropColumn('hr_approved_by');
            }
            if (Schema::hasColumn('payroll_periods', 'hr_approved_at')) {
                $table->dropColumn('hr_approved_at');
            }
            if (Schema::hasColumn('payroll_periods', 'branch_submitted_at')) {
                $table->dropColumn('branch_submitted_at');
            }
        });
    }
};
