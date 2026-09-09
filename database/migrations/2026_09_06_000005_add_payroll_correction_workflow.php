<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_periods', 'correction_stage')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->string('correction_stage', 30)->nullable()->after('finance_submitted_at');
                $table->text('correction_reason')->nullable()->after('correction_stage');
                $table->foreignId('correction_returned_by')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
                $table->timestamp('correction_returned_at')->nullable()->after('correction_returned_by');
            });
        }

        if (!Schema::hasColumn('payroll_entries', 'correction_stage')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->string('correction_stage', 30)->nullable()->after('payslip_sent_to');
                $table->text('correction_reason')->nullable()->after('correction_stage');
                $table->foreignId('correction_returned_by')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
                $table->timestamp('correction_returned_at')->nullable()->after('correction_returned_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_periods', 'correction_stage')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->dropForeign(['correction_returned_by']);
                $table->dropColumn(['correction_stage', 'correction_reason', 'correction_returned_by', 'correction_returned_at']);
            });
        }

        if (Schema::hasColumn('payroll_entries', 'correction_stage')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->dropForeign(['correction_returned_by']);
                $table->dropColumn(['correction_stage', 'correction_reason', 'correction_returned_by', 'correction_returned_at']);
            });
        }
    }
};
