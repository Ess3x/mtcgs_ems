<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employee_profiles')) {
            Schema::create('employee_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->string('employee_number')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('middle_name')->nullable();
                $table->string('suffix')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
                $table->enum('civil_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
                $table->string('position');
                $table->string('department')->nullable();
                $table->enum('employment_type', ['Regular', 'Probationary', 'Contractual', 'Part-time'])->default('Regular');
                $table->date('date_hired');
                $table->date('date_resigned')->nullable();
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('hourly_rate', 10, 2)->nullable();
                $table->string('contact_number')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_number')->nullable();
                $table->text('address')->nullable();
                $table->string('profile_photo')->nullable();
                $table->text('fingerprint_template')->nullable();
                $table->boolean('is_fingerprint_registered')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('employee_profiles');
    }
};
