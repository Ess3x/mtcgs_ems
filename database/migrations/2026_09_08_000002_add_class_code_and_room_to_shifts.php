<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->string('class_code', 50)->nullable()->after('name');
            $table->string('room', 100)->nullable()->after('class_code');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->dropColumn(['class_code', 'room']);
        });
    }
};