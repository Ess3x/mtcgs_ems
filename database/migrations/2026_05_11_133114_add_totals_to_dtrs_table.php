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
        Schema::table('dtrs', function (Blueprint $table) {
            $table->decimal('total_hours', 8, 2)->default(0)->after('period_end');
            $table->integer('days_present')->default(0)->after('total_hours');
            $table->integer('days_absent')->default(0)->after('days_present');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('days_absent');
            $table->integer('late_minutes')->default(0)->after('overtime_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->dropColumn(['total_hours', 'days_present', 'days_absent', 'overtime_hours', 'late_minutes']);
        });
    }
};
