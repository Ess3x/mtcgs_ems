<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        Schema::table('admin_profiles', function (Blueprint $table) {
            DB::statement("ALTER TABLE admin_profiles MODIFY COLUMN admin_level ENUM('super_admin', 'admin', 'support_admin', 'branch_admin') DEFAULT 'admin'");
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('admin_profiles', function (Blueprint $table) {
            DB::statement("ALTER TABLE admin_profiles MODIFY COLUMN admin_level ENUM('super_admin', 'admin', 'support_admin') DEFAULT 'admin'");
        });
    }
};
