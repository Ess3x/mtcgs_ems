<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts')) {
            DB::table('shifts')->where('start_time', '08:00:00')->update(['start_time' => '07:00:00']);
            Schema::table('shifts', function (Blueprint $table) {
                $table->time('start_time')->default('07:00:00')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->time('start_time')->default('08:00:00')->change();
            });
        }
    }
};