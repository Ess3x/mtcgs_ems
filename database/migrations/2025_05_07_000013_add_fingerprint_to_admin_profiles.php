<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_profiles', 'fingerprint_template')) {
                $table->text('fingerprint_template')->nullable()->after('address');
            }
            if (!Schema::hasColumn('admin_profiles', 'is_fingerprint_registered')) {
                $table->boolean('is_fingerprint_registered')->default(false)->after('fingerprint_template');
            }
            if (!Schema::hasColumn('admin_profiles', 'employee_profile_id')) {
                $table->foreignId('employee_profile_id')->nullable()->constrained('employee_profiles')->nullOnDelete();
            }
            if (!Schema::hasColumn('admin_profiles', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->default(0)->after('address');
            }
        });
    }

    public function down()
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropColumn(['fingerprint_template', 'is_fingerprint_registered', 'employee_profile_id', 'basic_salary']);
        });
    }
};
