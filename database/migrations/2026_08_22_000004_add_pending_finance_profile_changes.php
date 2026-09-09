<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->json('pending_changes')->nullable();
            $table->foreignId('changes_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changes_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('finance_profiles', function (Blueprint $table) {
            $table->dropForeign(['changes_requested_by']);
            $table->dropColumn(['pending_changes', 'changes_requested_by', 'changes_requested_at']);
        });
    }
};