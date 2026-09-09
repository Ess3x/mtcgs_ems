<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->decimal('sick_leave_total', 5, 1)->default(15);
            $table->decimal('sick_leave_used', 5, 1)->default(0);
            $table->decimal('vacation_leave_total', 5, 1)->default(15);
            $table->decimal('vacation_leave_used', 5, 1)->default(0);
            $table->decimal('emergency_leave_total', 5, 1)->default(3);
            $table->decimal('emergency_leave_used', 5, 1)->default(0);
            $table->timestamps();
            
            $table->unique(['employee_profile_id', 'year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_balances');
    }
};
