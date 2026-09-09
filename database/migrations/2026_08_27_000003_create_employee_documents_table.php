<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
            $table->index(['employee_profile_id', 'category']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_documents');
    }
};
