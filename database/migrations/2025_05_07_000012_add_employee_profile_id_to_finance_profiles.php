<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('finance_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_profiles', 'employee_profile_id')) {
                $table->foreignId('employee_profile_id')->nullable()->constrained('employee_profiles')->nullOnDelete();
            }
            if (!Schema::hasColumn('finance_profiles', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->default(0)->after('address');
            }
            if (!Schema::hasColumn('finance_profiles', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('basic_salary');
            }
        });
    }

    public function down()
    {
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->dropForeign(['employee_profile_id']);
            $table->dropColumn(['employee_profile_id', 'basic_salary', 'hourly_rate']);
        });
    }
};
