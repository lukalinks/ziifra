<?php

namespace App\Enums;

enum PayrollAllowanceKind: string
{
    case Recurring = 'recurring';
    case OneOff = 'one_off';

    public function label(): string
    {
        return match ($this) {
            self::Recurring => __('payroll.allowance_kind_recurring'),
            self::OneOff => __('payroll.allowance_kind_one_off'),
        };
    }
}
