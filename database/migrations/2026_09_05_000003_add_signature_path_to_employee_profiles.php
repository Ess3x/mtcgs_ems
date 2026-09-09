<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employee_profiles', 'signature_path')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->string('signature_path')->nullable()->after('profile_photo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_profiles', 'signature_path')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }
    }
};