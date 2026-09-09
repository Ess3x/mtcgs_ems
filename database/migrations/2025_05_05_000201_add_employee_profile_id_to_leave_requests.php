<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'employee_profile_id')) {
                $table->unsignedBigInteger('employee_profile_id')->nullable()->after('id');
                $table->foreign('employee_profile_id')
                    ->references('id')
                    ->on('employee_profiles')
                    ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['employee_profile_id']);
            $table->dropColumn('employee_profile_id');
        });
    }
};
