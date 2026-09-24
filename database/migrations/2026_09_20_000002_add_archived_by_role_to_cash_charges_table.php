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
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->string('archived_by_role')->nullable()->after('archived_from_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->dropColumn('archived_by_role');
        });
    }
};
