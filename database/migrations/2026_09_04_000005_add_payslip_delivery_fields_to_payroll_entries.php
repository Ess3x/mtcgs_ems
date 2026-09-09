<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table): void {
            if (!Schema::hasColumn('payroll_entries', 'payslip_sent_at')) {
                $table->timestamp('payslip_sent_at')->nullable();
            }
            if (!Schema::hasColumn('payroll_entries', 'payslip_sent_to')) {
                $table->string('payslip_sent_to')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_entries', 'payslip_sent_at')) {
                $table->dropColumn('payslip_sent_at');
            }
            if (Schema::hasColumn('payroll_entries', 'payslip_sent_to')) {
                $table->dropColumn('payslip_sent_to');
            }
        });
    }
};
