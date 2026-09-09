<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class WorkingDayService
{
    public function isHoliday(Carbon|string $date, ?int $branchId = null): bool
    {
        $day = $date instanceof Carbon
            ? $date->copy()->timezone(config('app.timezone'))
            : Carbon::parse($date, config('app.timezone'));

        $monthDay = $day->format('m-d');
        $configuredHoliday = in_array($monthDay, config('philippine_holidays.recurring', []), true)
            || in_array($day->toDateString(), config('philippine_holidays.dates.' . $day->year, []), true);

        if ($configuredHoliday) {
            return true;
        }

        if (!Schema::hasTable('calendar_events')) {
            return false;
        }

        return CalendarEvent::holidays()
            ->whereDate('event_date', $day->toDateString())
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id');
                if ($branchId !== null) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->exists();
    }

    public function isWorkingDay(Carbon|string $date, ?int $branchId = null): bool
    {
        $day = $date instanceof Carbon ? $date : Carbon::parse($date, config('app.timezone'));

        return !$day->isWeekend() && !$this->isHoliday($day, $branchId);
    }

    public function countWorkingDays(Carbon|string $start, Carbon|string $end, ?int $branchId = null): int
    {
        $current = $start instanceof Carbon ? $start->copy() : Carbon::parse($start, config('app.timezone'));
        $last = $end instanceof Carbon ? $end->copy() : Carbon::parse($end, config('app.timezone'));
        $days = 0;

        while ($current->lte($last)) {
            if ($this->isWorkingDay($current, $branchId)) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }
}