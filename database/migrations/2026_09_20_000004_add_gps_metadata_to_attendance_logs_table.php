<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->decimal('gps_latitude', 10, 7)->nullable()->after('location');
            $table->decimal('gps_longitude', 10, 7)->nullable()->after('gps_latitude');
            $table->decimal('gps_accuracy', 8, 2)->nullable()->after('gps_longitude');
            $table->dateTime('gps_timestamp')->nullable()->after('gps_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['gps_latitude', 'gps_longitude', 'gps_accuracy', 'gps_timestamp']);
        });
    }
};