<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('payroll_periods', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('name')->constrained('branches')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }

            if (Schema::hasColumn('payroll_periods', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
