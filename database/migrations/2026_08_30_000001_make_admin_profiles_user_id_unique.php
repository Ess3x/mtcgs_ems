<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('admin_profiles')) {
            return;
        }

        $duplicates = DB::table('admin_profiles')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicates as $userId) {
            $rows = DB::table('admin_profiles')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $keepId = (int) $rows->first()->id;

            DB::table('admin_profiles')
                ->where('user_id', $userId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_profiles')) {
            Schema::table('admin_profiles', function (Blueprint $table) {
                $table->dropUnique(['user_id']);
            });
        }
    }
};
