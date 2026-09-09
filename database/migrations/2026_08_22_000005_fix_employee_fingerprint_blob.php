<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('employee_profiles') && Schema::hasColumn('employee_profiles', 'fingerprint_template')) {
            DB::statement('ALTER TABLE `employee_profiles` MODIFY `fingerprint_template` MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('employee_profiles') && Schema::hasColumn('employee_profiles', 'fingerprint_template')) {
            DB::statement('ALTER TABLE `employee_profiles` MODIFY `fingerprint_template` TEXT NULL');
        }
    }
};
