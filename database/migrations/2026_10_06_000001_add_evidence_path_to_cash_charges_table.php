<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->string('evidence_path')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->dropColumn('evidence_path');
        });
    }
};
