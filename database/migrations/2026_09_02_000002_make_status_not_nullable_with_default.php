<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_profiles') && Schema::hasColumn('employee_profiles', 'status')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->string('status')->default('New Hire')->change();
            });
        }

        if (Schema::hasTable('finance_profiles') && Schema::hasColumn('finance_profiles', 'status')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->string('status')->default('New Hire')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_profiles') && Schema::hasColumn('employee_profiles', 'status')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->string('status')->nullable()->change();
            });
        }

        if (Schema::hasTable('finance_profiles') && Schema::hasColumn('finance_profiles', 'status')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->string('status')->nullable()->change();
            });
        }
    }
};
