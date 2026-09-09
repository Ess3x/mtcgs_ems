<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('dtrs')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'employee_profile_id', 'period_start', 'period_end'])
            ->each(function (object $dtr): void {
                $start = Carbon::parse($dtr->period_start);
                $end = Carbon::parse($dtr->period_end);
                $normalizedStart = null;
                $normalizedEnd = null;

                if ($start->day === 3 && $end->isSameMonth($start) && $end->day === 18) {
                    $normalizedStart = $start->copy()->startOfMonth();
                    $normalizedEnd = $start->copy()->day(15);
                } elseif (
                    $start->day === 19
                    && $end->day === 3
                    && $end->isSameDay($start->copy()->addMonthNoOverflow()->day(3))
                ) {
                    $normalizedStart = $start->copy()->day(16);
                    $normalizedEnd = $start->copy()->endOfMonth();
                }

                if (!$normalizedStart || !$normalizedEnd) {
                    return;
                }

                $targetExists = DB::table('dtrs')
                    ->whereNull('deleted_at')
                    ->where('employee_profile_id', $dtr->employee_profile_id)
                    ->whereDate('period_start', $normalizedStart->toDateString())
                    ->whereDate('period_end', $normalizedEnd->toDateString())
                    ->where('id', '!=', $dtr->id)
                    ->exists();

                if (!$targetExists) {
                    DB::table('dtrs')
                        ->where('id', $dtr->id)
                        ->update([
                            'period_start' => $normalizedStart->toDateString(),
                            'period_end' => $normalizedEnd->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Legacy cutoff dates cannot be restored without knowing the original release mapping.
    }
};