<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resignation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->date('resignation_date');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->decimal('salary_due', 12, 2)->nullable();
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('final_pay', 12, 2)->nullable();
            $table->timestamp('final_pay_computed_at')->nullable();
            $table->timestamps();
            $table->index(['employee_profile_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('resignation_requests');
    }
};
