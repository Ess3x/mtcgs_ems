<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admin_profiles')) {
            Schema::create('admin_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('employee_number')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('middle_name')->nullable();
                $table->string('position')->default('System Administrator');
                $table->string('department')->default('IT Administration');
                $table->enum('admin_level', ['super_admin', 'admin', 'support_admin'])->default('admin');
                $table->json('permissions')->nullable(); // Custom permissions
                $table->date('date_hired')->nullable();
                $table->string('contact_number')->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_profiles');
    }
};
