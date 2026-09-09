<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('dtrs')) {
            DB::statement("ALTER TABLE `dtrs` MODIFY `status` ENUM('draft', 'submitted', 'pending_system_admin', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('dtrs')) {
            DB::statement("ALTER TABLE `dtrs` MODIFY `status` ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
        }
    }
};
