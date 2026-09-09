<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_profiles', 'can_verify_ids')) {
                $table->boolean('can_verify_ids')->default(false)->after('admin_level');
            }
        });
    }

    public function down()
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('admin_profiles', 'can_verify_ids')) {
                $table->dropColumn('can_verify_ids');
            }
        });
    }
};
