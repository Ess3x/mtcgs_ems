<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_profiles') && !Schema::hasColumn('employee_profiles', 'status')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->string('status')->default('New Hire')->after('date_hired');
            });
        }

        if (Schema::hasTable('finance_profiles') && !Schema::hasColumn('finance_profiles', 'status')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->string('status')->default('New Hire')->after('date_hired');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_profiles') && Schema::hasColumn('employee_profiles', 'status')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('finance_profiles') && Schema::hasColumn('finance_profiles', 'status')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};