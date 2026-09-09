<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (['employee_profiles', 'finance_profiles', 'admin_profiles'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'fingerprint_template')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `fingerprint_template` MEDIUMBLOB NULL");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (['employee_profiles', 'finance_profiles', 'admin_profiles'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'fingerprint_template')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `fingerprint_template` TEXT NULL");
            }
        }
    }
};
