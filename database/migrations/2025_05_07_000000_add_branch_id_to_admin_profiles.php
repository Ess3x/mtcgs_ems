<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // I-update ang users table para may admin_type
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_type')) {
                $table->enum('admin_type', ['super_admin', 'branch_admin'])->nullable()->after('role');
            }
        });
        
        // I-add ang branch_id sa admin_profiles
        Schema::table('admin_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_profiles', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_type');
        });
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
