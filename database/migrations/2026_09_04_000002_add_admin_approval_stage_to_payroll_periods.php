<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_periods', 'admin_approval_stage')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->string('admin_approval_stage', 20)->nullable()->after('approved_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_periods', 'admin_approval_stage')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->dropColumn('admin_approval_stage');
            });
        }
    }
};
