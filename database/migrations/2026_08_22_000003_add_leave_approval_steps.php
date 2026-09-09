<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('branch_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('branch_approved_at')->nullable();
            $table->foreignId('system_admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('system_admin_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['branch_approved_by']);
            $table->dropForeign(['system_admin_approved_by']);
            $table->dropColumn([
                'branch_approved_by', 'branch_approved_at',
                'system_admin_approved_by', 'system_admin_approved_at',
            ]);
        });
    }
};