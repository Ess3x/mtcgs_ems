<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payroll_entries')) {
            Schema::create('payroll_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
                $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->decimal('basic_pay', 12, 2)->default(0);
                $table->decimal('overtime_pay', 12, 2)->default(0);
                $table->decimal('overtime_hours', 8, 2)->default(0);
                $table->integer('days_present')->default(0);
                $table->integer('days_absent')->default(0);
                $table->decimal('late_deduction', 12, 2)->default(0);
                $table->decimal('allowances', 12, 2)->default(0);
                $table->decimal('bonuses', 12, 2)->default(0);
                $table->decimal('gross_pay', 12, 2)->default(0);
                $table->decimal('sss_contribution', 12, 2)->default(0);
                $table->decimal('philhealth_contribution', 12, 2)->default(0);
                $table->decimal('pagibig_contribution', 12, 2)->default(0);
                $table->decimal('withholding_tax', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->string('status')->default('draft');
                $table->timestamps();
                
                $table->index(['payroll_period_id', 'employee_profile_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payroll_entries');
    }
};
