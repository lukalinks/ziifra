<?php

namespace App\Enums;

enum DailyHoursApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('daily_hours.status_pending'),
            self::Approved => __('daily_hours.status_approved'),
        };
    }
}
