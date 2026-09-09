<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('employee_number', 50)->unique();
                $table->string('first_name', 50);
                $table->string('last_name', 50);
                $table->string('middle_name', 50)->nullable();
                $table->string('position', 100)->nullable();
                $table->string('department', 100)->nullable();
                $table->date('date_hired')->nullable();
                $table->decimal('basic_salary', 12, 2)->nullable();
                $table->string('contact_number', 20)->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
