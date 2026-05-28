<?php

namespace App\Enums;

enum OrganizationLegalForm: string
{
    case Shpk = 'shpk';
    case BusinessIndividual = 'bi';
    case BranchOfOther = 'branch';
    case Partnership = 'partnership';

    public function label(): string
    {
        return match ($this) {
            self::Shpk => 'SH.P.K',
            self::BusinessIndividual => 'BI — Business Individual',
            self::BranchOfOther => 'Branch of Other Company',
            self::Partnership => 'Partnership',
        };
    }
}
