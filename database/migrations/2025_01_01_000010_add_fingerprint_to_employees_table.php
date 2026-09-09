<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'fingerprint_template')) {
                $table->text('fingerprint_template')->nullable()->after('basic_salary');
            }
            if (!Schema::hasColumn('employees', 'is_fingerprint_registered')) {
                $table->boolean('is_fingerprint_registered')->default(false)->after('fingerprint_template');
            }
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['fingerprint_template', 'is_fingerprint_registered']);
        });
    }
};
