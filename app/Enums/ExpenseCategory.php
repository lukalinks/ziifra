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
        return __('expenses.categories.'.$this->value);
    }
}
