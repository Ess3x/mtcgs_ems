<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resignation_request_id')->constrained('resignation_requests')->cascadeOnDelete();
            $table->string('department');
            $table->string('item');
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clearance_items');
    }
};
