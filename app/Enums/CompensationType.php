<?php

namespace App\Enums;

enum CompensationType: string
{
    case Hourly = 'hourly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => __('employees.compensation_hourly'),
            self::Monthly => __('employees.compensation_monthly'),
        };
    }
}
