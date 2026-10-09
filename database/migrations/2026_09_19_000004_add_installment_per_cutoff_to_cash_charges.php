<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->decimal('installment_per_cutoff', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('cash_charges', function (Blueprint $table) {
            $table->dropColumn('installment_per_cutoff');
        });
    }
};
