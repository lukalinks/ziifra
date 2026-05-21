<?php

namespace App\Enums;

enum AllowanceTaxTreatment: string
{
    case Taxable = 'taxable';
    /** Treated as non-wage / exempt for PIT & pension in this app; confirm each item with ATK / your advisor. */
    case ExemptStatutory = 'exempt_statutory';

    public function label(): string
    {
        return match ($this) {
            self::Taxable => __('payroll.allowance_tax_taxable'),
            self::ExemptStatutory => __('payroll.allowance_tax_exempt'),
        };
    }
}
