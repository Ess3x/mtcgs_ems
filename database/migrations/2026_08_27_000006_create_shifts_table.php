<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->string('working_days')->default('Mon,Tue,Wed,Thu,Fri');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
