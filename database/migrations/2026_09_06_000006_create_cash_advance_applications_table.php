<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_advance_applications')) {
            return;
        }

        Schema::create('cash_advance_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('installment_amount', 12, 2)->nullable();
            $table->unsignedInteger('installments')->default(1);
            $table->unsignedInteger('deducted_installments')->default(0);
            $table->timestamp('deduction_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('purpose');
            $table->string('eligibility_category', 40);
            $table->string('status', 30)->default('pending_fo');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('fo_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fo_reviewed_at')->nullable();
            $table->foreignId('bh_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('bh_reviewed_at')->nullable();
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->foreignId('fh_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fh_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'employee_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_applications');
    }
};
