<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shift_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['employee_profile_id', 'shift_id']);
        });

        DB::table('employee_profiles')
            ->whereNotNull('shift_id')
            ->select(['id', 'shift_id'])
            ->get()
            ->each(function (object $employee): void {
                DB::table('employee_shift_assignments')->insert([
                    'employee_profile_id' => $employee->id,
                    'shift_id' => $employee->shift_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_assignments');
    }
};