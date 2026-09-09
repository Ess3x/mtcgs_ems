<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Keep email, password, role, verification
            // Remove old employee-specific columns that will go to profiles
            if (Schema::hasColumn('users', 'employee_number')) {
                $table->dropColumn('employee_number');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('users', 'basic_salary')) {
                $table->dropColumn('basic_salary');
            }
            
            // Add profile reference
            if (!Schema::hasColumn('users', 'profile_id')) {
                $table->unsignedBigInteger('profile_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'profile_type')) {
                $table->string('profile_type')->nullable()->after('profile_id');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_id', 'profile_type']);
        });
    }
};
