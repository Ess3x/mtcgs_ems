<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('finance_profiles')) {
            Schema::create('finance_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('employee_number')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('middle_name')->nullable();
                $table->string('position')->default('Finance Officer');
                $table->string('department')->default('Finance Department');
                $table->decimal('salary_grade', 5, 2)->nullable();
                $table->json('accessible_branches')->nullable(); // Which branches they can access
                $table->boolean('can_process_payroll')->default(true);
                $table->boolean('can_approve_payroll')->default(false);
                $table->date('date_hired')->nullable();
                $table->string('contact_number')->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('finance_profiles');
    }
};
