<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_balances', 'service_incentive_leave_total')) {
            Schema::table('leave_balances', function (Blueprint $table): void {
                $table->decimal('service_incentive_leave_total', 5, 1)->default(5)->after('paternity_leave_used');
                $table->decimal('service_incentive_leave_used', 5, 1)->default(0)->after('service_incentive_leave_total');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->dropColumn(['service_incentive_leave_total', 'service_incentive_leave_used']);
        });
    }
};
