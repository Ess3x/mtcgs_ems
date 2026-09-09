<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_logs', 'break_in')) $table->dateTime('break_in')->nullable()->after('am_out');
            if (!Schema::hasColumn('attendance_logs', 'break_out')) $table->dateTime('break_out')->nullable()->after('break_in');
            if (!Schema::hasColumn('attendance_logs', 'override_status')) $table->string('override_status')->nullable()->after('verification_method');
            if (!Schema::hasColumn('attendance_logs', 'override_reason')) $table->text('override_reason')->nullable()->after('override_status');
            if (!Schema::hasColumn('attendance_logs', 'override_requested_by')) $table->foreignId('override_requested_by')->nullable()->after('override_reason')->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('attendance_logs', 'override_reviewed_by')) $table->foreignId('override_reviewed_by')->nullable()->after('override_requested_by')->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('attendance_logs', 'override_reviewed_at')) $table->timestamp('override_reviewed_at')->nullable()->after('override_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            foreach (['override_requested_by', 'override_reviewed_by'] as $foreign) {
                if (Schema::hasColumn('attendance_logs', $foreign)) $table->dropForeign([$foreign]);
            }
            $table->dropColumn(['break_in', 'break_out', 'override_status', 'override_reason', 'override_requested_by', 'override_reviewed_by', 'override_reviewed_at']);
        });
    }
};
