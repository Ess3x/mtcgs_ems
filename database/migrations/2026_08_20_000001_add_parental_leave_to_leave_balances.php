<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_balances', 'maternity_leave_total')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                $table->decimal('maternity_leave_total', 5, 1)->default(0)->after('emergency_leave_used');
                $table->decimal('maternity_leave_used', 5, 1)->default(0)->after('maternity_leave_total');
                $table->decimal('paternity_leave_total', 5, 1)->default(0)->after('maternity_leave_used');
                $table->decimal('paternity_leave_used', 5, 1)->default(0)->after('paternity_leave_total');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn([
                'maternity_leave_total', 'maternity_leave_used',
                'paternity_leave_total', 'paternity_leave_used',
            ]);
        });
    }
};