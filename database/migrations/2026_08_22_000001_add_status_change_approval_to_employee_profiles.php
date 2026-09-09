<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('pending_status')->nullable()->after('status');
            $table->foreignId('status_change_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_change_requested_at')->nullable();
            $table->foreignId('status_change_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_change_approved_at')->nullable();
            $table->text('status_change_rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropForeign(['status_change_requested_by']);
            $table->dropForeign(['status_change_approved_by']);
            $table->dropColumn([
                'pending_status', 'status_change_requested_by', 'status_change_requested_at',
                'status_change_approved_by', 'status_change_approved_at', 'status_change_rejection_reason',
            ]);
        });
    }
};