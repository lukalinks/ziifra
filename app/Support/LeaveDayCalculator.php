<?php

namespace App\Support;

use App\Models\Organization;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveDayCalculator
{
    /**
     * Count working days in the range using the organization's work week.
     */
    public static function countDays(Organization $organization, Carbon|string $start, Carbon|string $end): float
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $workDays = $organization->workWeekDayValues();
        $count = 0;

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $key = self::isoDayToWorkWeekKey($day->dayOfWeekIso);

            if (in_array($key, $workDays, true)) {
                $count++;
            }
        }

        return (float) $count;
    }

    protected static function isoDayToWorkWeekKey(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
            default => 'mon',
        };
    }
}
