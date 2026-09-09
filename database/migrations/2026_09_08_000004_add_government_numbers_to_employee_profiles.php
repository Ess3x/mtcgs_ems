<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->string('sss_number', 30)->nullable()->after('signature_path');
            $table->string('philhealth_number', 30)->nullable()->after('sss_number');
            $table->string('pagibig_number', 30)->nullable()->after('philhealth_number');
            $table->string('tin_number', 30)->nullable()->after('pagibig_number');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->dropColumn(['sss_number', 'philhealth_number', 'pagibig_number', 'tin_number']);
        });
    }
};