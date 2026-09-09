<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employee_additional_deductions')) {
            Schema::create('employee_additional_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_entry_id')->constrained('payroll_entries')->cascadeOnDelete();
                $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
                $table->string('deduction_type'); // e.g., 'additional_pagibig', 'loan', 'insurance', 'custom'
                $table->string('description')->nullable();
                $table->decimal('amount', 12, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['payroll_entry_id', 'employee_profile_id'], 'emp_deductions_payroll_emp_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('employee_additional_deductions');
    }
};
