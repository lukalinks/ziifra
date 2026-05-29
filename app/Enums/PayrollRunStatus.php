<?php

namespace App\Enums;

enum PayrollRunStatus: string
{
    case Draft = 'draft';
    case Locked = 'locked';

    public function label(): string
    {
        return __('payroll.run_statuses.'.$this->value);
    }
}
