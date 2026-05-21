<?php

namespace App\Enums;

enum WorkWeekDay: string
{
    case Mon = 'mon';
    case Tue = 'tue';
    case Wed = 'wed';
    case Thu = 'thu';
    case Fri = 'fri';
    case Sat = 'sat';
    case Sun = 'sun';

    public function label(): string
    {
        return match ($this) {
            self::Mon => 'Monday',
            self::Tue => 'Tuesday',
            self::Wed => 'Wednesday',
            self::Thu => 'Thursday',
            self::Fri => 'Friday',
            self::Sat => 'Saturday',
            self::Sun => 'Sunday',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Mon => 'Mon',
            self::Tue => 'Tue',
            self::Wed => 'Wed',
            self::Thu => 'Thu',
            self::Fri => 'Fri',
            self::Sat => 'Sat',
            self::Sun => 'Sun',
        };
    }

    /**
     * @return list<self>
     */
    public static function defaultWorkWeek(): array
    {
        return [self::Mon, self::Tue, self::Wed, self::Thu, self::Fri];
    }
}
