<?php

namespace App\Enums;

enum OrganizationLegalForm: string
{
    case Shpk = 'shpk';
    case Sha = 'sha';
    case Individual = 'individual';
    case Ngo = 'ngo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Shpk => 'SHPK (LLC)',
            self::Sha => 'SH.A. (JSC)',
            self::Individual => 'Individual business',
            self::Ngo => 'NGO / Non-profit',
            self::Other => 'Other',
        };
    }
}
