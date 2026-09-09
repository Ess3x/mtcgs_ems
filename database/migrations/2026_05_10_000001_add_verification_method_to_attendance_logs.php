<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_logs', 'verification_method')) {
                $table->string('verification_method')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'verification_method')) {
                $table->dropColumn('verification_method');
            }
        });
    }
};
