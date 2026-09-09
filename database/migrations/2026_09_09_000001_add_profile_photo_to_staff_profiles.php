<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['finance_profiles', 'admin_profiles'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'profile_photo')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('profile_photo')->nullable()->after('address');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['finance_profiles', 'admin_profiles'] as $tableName) {
            if (Schema::hasColumn($tableName, 'profile_photo')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('profile_photo');
                });
            }
        }
    }
};
