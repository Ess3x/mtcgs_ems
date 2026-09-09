<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `dtrs` MODIFY `status` ENUM('draft', 'submitted', 'pending_system_admin', 'pending_finance_head', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `dtrs` MODIFY `status` ENUM('draft', 'submitted', 'pending_system_admin', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
    }
};
