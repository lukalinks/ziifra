<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Travel = 'travel';
    case Meals = 'meals';
    case Office = 'office';
    case Equipment = 'equipment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Travel => 'Travel',
            self::Meals => 'Meals & entertainment',
            self::Office => 'Office supplies',
            self::Equipment => 'Equipment',
            self::Other => 'Other',
        };
    }
}
