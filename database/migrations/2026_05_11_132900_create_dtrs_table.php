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
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->onDelete('cascade');
            $table->date('period_start'); // Start date (1st or 16th of month)
            $table->date('period_end');   // End date (15th or last day of month)
            $table->enum('status', ['draft', 'submitted', 'pending_system_admin', 'approved', 'rejected'])->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for faster queries
            $table->index('employee_profile_id');
            $table->index('period_start');
            $table->index('period_end');
            $table->unique(['employee_profile_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs');
    }
};