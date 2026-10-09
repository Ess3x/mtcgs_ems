<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceTimeRules
{
    public static function lateMinutes(Carbon $actualTime, Carbon $scheduledStart, int $graceMinutes = 0): int
    {
        if (!$actualTime->gt($scheduledStart)) {
            return 0;
        }

        $lateSeconds = $scheduledStart->diffInSeconds($actualTime);

        return max(1, (int) ceil($lateSeconds / 60));
    }
}