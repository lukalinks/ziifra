<?php

namespace App\Enums;

enum PayrollGenerationMode: string
{
    case All = 'all';
    case Individual = 'individual';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::All => __('payroll.generation.all'),
            self::Individual => __('payroll.generation.individual'),
            self::Group => __('payroll.generation.group'),
        };
    }
}
