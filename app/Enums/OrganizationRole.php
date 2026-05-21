<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Hr = 'hr';
    case Manager = 'manager';
    case Employee = 'employee';

    public function canInvite(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr], true);
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    /** Owner and Admin — define organization-wide employee custom field schemas. */
    public function canManageEmployeeFieldDefinitions(): bool
    {
        return $this->canManageOrganization();
    }

    public function canManageBilling(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr], true);
    }

    public function canManageFinance(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr], true);
    }

    public function canManageEmployees(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr], true);
    }

    public function canViewEmployees(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr, self::Manager], true);
    }

    public function canViewAllLeave(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr, self::Manager], true);
    }

    public function canViewLeave(): bool
    {
        return $this->canViewAllLeave() || $this === self::Employee;
    }

    public function canManageLeave(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Hr], true);
    }

    public function canRequestOwnLeave(): bool
    {
        return $this === self::Employee;
    }

    /** Owner, Admin, and HR — full company operations dashboard. */
    public function usesAdminDashboard(): bool
    {
        return $this->canManageEmployees();
    }

    public function usesTeamDashboard(): bool
    {
        return $this === self::Manager;
    }

    public function usesEmployeeDashboard(): bool
    {
        return $this === self::Employee;
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Hr => 'HR',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }
}
