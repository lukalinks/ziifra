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
        return __('time.week_days.'.$this->value);
    }

    public function shortLabel(): string
    {
        return __('time.week_days_short.'.$this->value);
    }

    /**
     * @return list<self>
     */
    public static function defaultWorkWeek(): array
    {
        return [self::Mon, self::Tue, self::Wed, self::Thu, self::Fri];
    }
}
