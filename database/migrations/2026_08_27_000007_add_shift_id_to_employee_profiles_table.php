<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('employee_profiles', 'shift_id')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->foreignId('shift_id')->nullable()->after('branch_id')->constrained('shifts')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('employee_profiles', 'shift_id')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            });
        }
    }
};
