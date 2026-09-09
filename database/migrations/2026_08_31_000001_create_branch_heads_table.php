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
        Schema::create('branch_heads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_profile_id')->nullable();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('position')->default('Branch Head');
            $table->string('department')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable();
            $table->date('date_hired')->nullable();
            $table->longText('fingerprint_template')->nullable();
            $table->boolean('is_fingerprint_registered')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_heads');
    }
};
