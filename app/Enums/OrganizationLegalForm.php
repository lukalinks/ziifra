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
        return __('settings.company.legal_forms.'.$this->value);
    }
}
