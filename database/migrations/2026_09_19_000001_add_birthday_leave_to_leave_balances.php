<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_balances', 'birthday_leave_total')) {
                $table->decimal('birthday_leave_total', 5, 1)->default(1)->after('emergency_leave_used');
            }

            if (!Schema::hasColumn('leave_balances', 'birthday_leave_used')) {
                $table->decimal('birthday_leave_used', 5, 1)->default(0)->after('birthday_leave_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (Schema::hasColumn('leave_balances', 'birthday_leave_used')) {
                $table->dropColumn('birthday_leave_used');
            }

            if (Schema::hasColumn('leave_balances', 'birthday_leave_total')) {
                $table->dropColumn('birthday_leave_total');
            }
        });
    }
};
