<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_entries', 'payslip_published_at')) {
                $table->timestamp('payslip_published_at')->nullable()->after('payslip_sent_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_entries', 'payslip_published_at')) {
                $table->dropColumn('payslip_published_at');
            }
        });
    }
};