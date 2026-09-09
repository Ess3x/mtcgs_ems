<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'branch_approved_by')) {
                $table->foreignId('branch_approved_by')->nullable()->after('branch_submitted_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'branch_approved_at')) {
                $table->timestamp('branch_approved_at')->nullable()->after('branch_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'branch_approved_by')) {
                $table->dropForeign(['branch_approved_by']);
                $table->dropColumn('branch_approved_by');
            }
            if (Schema::hasColumn('payroll_periods', 'branch_approved_at')) {
                $table->dropColumn('branch_approved_at');
            }
        });
    }
};