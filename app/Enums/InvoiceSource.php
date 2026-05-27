<?php

namespace App\Enums;

enum InvoiceSource: string
{
    case Manual = 'manual';
    case Hours = 'hours';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('invoices.source.manual'),
            self::Hours => __('invoices.source.hours'),
        };
    }
}
