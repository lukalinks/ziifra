<?php

namespace App\Enums;

enum EmployeeLoginStatus: string
{
    case Active = 'active';
    case PendingInvitation = 'pending';
    case NoEmail = 'no_email';
    case NotActivated = 'not_activated';

    public function label(): string
    {
        return __("employees.login_status.{$this->value}");
    }
}
