<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_entries', 'cash_charge_deduction')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->decimal('cash_charge_deduction', 12, 2)->default(0)->after('cash_advance_deduction');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_entries', 'cash_charge_deduction')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->dropColumn('cash_charge_deduction');
            });
        }
    }
};