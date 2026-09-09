<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `payroll_periods` MODIFY `status` ENUM('draft', 'processing', 'completed', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `payroll_periods` MODIFY `status` ENUM('draft', 'processing', 'completed', 'approved') NOT NULL DEFAULT 'draft'");
    }
};
