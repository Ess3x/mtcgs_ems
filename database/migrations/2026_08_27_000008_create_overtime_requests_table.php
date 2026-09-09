<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('attendance_log_id')->nullable()->constrained('attendance_logs')->nullOnDelete();
            $table->date('overtime_date');
            $table->decimal('hours', 5, 2);
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
            $table->index(['employee_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
