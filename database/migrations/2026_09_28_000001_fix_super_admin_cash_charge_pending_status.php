<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cash_charges')
            ->where('status', 'pending')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'cash_charges.requested_by')
                    ->where('users.role', 'admin')
                    ->where('users.admin_type', 'super_admin');
            })
            ->update(['status' => 'pending_super_admin']);
    }

    public function down(): void
    {
        DB::table('cash_charges')
            ->where('status', 'pending_super_admin')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'cash_charges.requested_by')
                    ->where('users.role', 'admin')
                    ->where('users.admin_type', 'super_admin');
            })
            ->update(['status' => 'pending']);
    }
};