<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('finance_profiles', 'signature_path')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->string('signature_path')->nullable()->after('fingerprint_template');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_profiles', 'signature_path')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }
    }
};