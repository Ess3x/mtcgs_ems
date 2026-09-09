<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'finance_submitted_by')) {
                $table->foreignId('finance_submitted_by')->nullable()->after('branch_approved_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'finance_submitted_at')) {
                $table->timestamp('finance_submitted_at')->nullable()->after('finance_submitted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'finance_submitted_by')) {
                $table->dropForeign(['finance_submitted_by']);
                $table->dropColumn('finance_submitted_by');
            }
            if (Schema::hasColumn('payroll_periods', 'finance_submitted_at')) {
                $table->dropColumn('finance_submitted_at');
            }
        });
    }
};