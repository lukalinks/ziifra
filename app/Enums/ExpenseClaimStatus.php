<?php

namespace App\Enums;

enum ExpenseClaimStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('statuses.'.$this->value);
    }
}
