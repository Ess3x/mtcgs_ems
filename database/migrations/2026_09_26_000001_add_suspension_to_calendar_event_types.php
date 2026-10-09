<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE calendar_events MODIFY event_type ENUM('activity', 'holiday', 'suspension') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('calendar_events')->where('event_type', 'suspension')->update(['event_type' => 'holiday']);
            DB::statement("ALTER TABLE calendar_events MODIFY event_type ENUM('activity', 'holiday') NOT NULL");
        }
    }
};