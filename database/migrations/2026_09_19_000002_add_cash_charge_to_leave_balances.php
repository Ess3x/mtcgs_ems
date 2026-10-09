<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_balances', 'cash_charge_total')) {
                $table->decimal('cash_charge_total', 5, 1)->default(0)->after('birthday_leave_used');
            }

            if (!Schema::hasColumn('leave_balances', 'cash_charge_used')) {
                $table->decimal('cash_charge_used', 5, 1)->default(0)->after('cash_charge_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (Schema::hasColumn('leave_balances', 'cash_charge_used')) {
                $table->dropColumn('cash_charge_used');
            }

            if (Schema::hasColumn('leave_balances', 'cash_charge_total')) {
                $table->dropColumn('cash_charge_total');
            }
        });
    }
};
