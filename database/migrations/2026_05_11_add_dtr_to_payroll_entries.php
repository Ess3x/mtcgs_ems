<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_entries', 'dtr_id')) {
                $table->foreignId('dtr_id')->nullable()->constrained('dtrs')->onDelete('cascade')->after('payroll_period_id');
            }
            if (!Schema::hasColumn('payroll_entries', 'absent_deduction')) {
                $table->decimal('absent_deduction', 12, 2)->nullable()->default(0)->after('late_deduction');
            }
            if (!Schema::hasColumn('payroll_entries', 'leave_deduction')) {
                $table->decimal('leave_deduction', 12, 2)->nullable()->default(0)->after('absent_deduction');
            }
            if (!Schema::hasColumn('payroll_entries', 'payroll_breakdown')) {
                $table->text('payroll_breakdown')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_entries', 'dtr_id')) {
                $table->dropForeignIdFor('dtrs');
            }
            if (Schema::hasColumn('payroll_entries', 'absent_deduction')) {
                $table->dropColumn('absent_deduction');
            }
            if (Schema::hasColumn('payroll_entries', 'leave_deduction')) {
                $table->dropColumn('leave_deduction');
            }
            if (Schema::hasColumn('payroll_entries', 'payroll_breakdown')) {
                $table->dropColumn('payroll_breakdown');
            }
        });
    }
};
