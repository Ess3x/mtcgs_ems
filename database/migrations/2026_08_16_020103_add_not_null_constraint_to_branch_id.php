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
        // Update foreign key constraints to RESTRICT instead of SET NULL
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
        });
        
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
        });
        
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
        });
        
        // Now add NOT NULL constraint
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
        
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
        
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: make branch_id nullable again and restore original foreign key
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(true)->change();
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
        
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(true)->change();
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
        
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(true)->change();
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }
};
