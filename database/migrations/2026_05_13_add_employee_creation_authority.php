<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add can_create_employees to admin_profiles
        if (Schema::hasTable('admin_profiles')) {
            Schema::table('admin_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_profiles', 'can_create_employees')) {
                    $table->boolean('can_create_employees')->default(false)->after('can_verify_ids');
                }
                if (!Schema::hasColumn('admin_profiles', 'can_manage_accounts')) {
                    $table->boolean('can_manage_accounts')->default(false)->after('can_create_employees');
                }
                if (!Schema::hasColumn('admin_profiles', 'authority_granted_by')) {
                    $table->unsignedBigInteger('authority_granted_by')->nullable()->after('can_manage_accounts');
                }
                if (!Schema::hasColumn('admin_profiles', 'authority_granted_at')) {
                    $table->timestamp('authority_granted_at')->nullable()->after('authority_granted_by');
                }
            });
        }

        // Add can_create_employees to finance_profiles
        if (Schema::hasTable('finance_profiles')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('finance_profiles', 'can_create_employees')) {
                    $table->boolean('can_create_employees')->default(false)->after('can_approve_payroll');
                }
                if (!Schema::hasColumn('finance_profiles', 'can_manage_accounts')) {
                    $table->boolean('can_manage_accounts')->default(false)->after('can_create_employees');
                }
                if (!Schema::hasColumn('finance_profiles', 'authority_granted_by')) {
                    $table->unsignedBigInteger('authority_granted_by')->nullable()->after('can_manage_accounts');
                }
                if (!Schema::hasColumn('finance_profiles', 'authority_granted_at')) {
                    $table->timestamp('authority_granted_at')->nullable()->after('authority_granted_by');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('admin_profiles')) {
            Schema::table('admin_profiles', function (Blueprint $table) {
                $table->dropColumnIfExists('can_create_employees');
                $table->dropColumnIfExists('can_manage_accounts');
                $table->dropColumnIfExists('authority_granted_by');
                $table->dropColumnIfExists('authority_granted_at');
            });
        }

        if (Schema::hasTable('finance_profiles')) {
            Schema::table('finance_profiles', function (Blueprint $table) {
                $table->dropColumnIfExists('can_create_employees');
                $table->dropColumnIfExists('can_manage_accounts');
                $table->dropColumnIfExists('authority_granted_by');
                $table->dropColumnIfExists('authority_granted_at');
            });
        }
    }
};
