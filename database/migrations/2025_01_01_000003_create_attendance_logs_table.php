<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->date('attendance_date');
                $table->datetime('time_in')->nullable();
                $table->datetime('time_out')->nullable();
                $table->string('status')->default('absent');
                $table->integer('late_minutes')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};
