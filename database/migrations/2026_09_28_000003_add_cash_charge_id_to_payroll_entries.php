<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_entries', 'cash_charge_id')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->foreignId('cash_charge_id')->nullable()->after('cash_advance_deduction')
                    ->constrained('cash_charges')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_entries', 'cash_charge_id')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cash_charge_id');
            });
        }
    }
};