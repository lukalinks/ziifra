<?php

namespace App\Enums;

enum PlanFeature: string
{
    case Employees = 'employees';
    case Leave = 'leave';
    case Documents = 'documents';
    case TeamInvitations = 'team_invitations';
    case Departments = 'departments';
    case EmployeeImport = 'employee_import';
    case Payroll = 'payroll';
    case Reports = 'reports';
    case Projects = 'projects';
    case TimeTracking = 'time_tracking';
    case Invoices = 'invoices';
    case Expenses = 'expenses';
    case Chat = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::Employees => __('billing.plan_features.employees'),
            self::Leave => __('billing.plan_features.leave'),
            self::Documents => __('billing.plan_features.documents'),
            self::TeamInvitations => __('billing.plan_features.team_invitations'),
            self::Departments => __('billing.plan_features.departments'),
            self::EmployeeImport => __('billing.plan_features.employee_import'),
            self::Payroll => __('billing.plan_features.payroll'),
            self::Reports => __('billing.plan_features.reports'),
            self::Projects => __('billing.plan_features.projects'),
            self::TimeTracking => __('billing.plan_features.time_tracking'),
            self::Invoices => __('billing.plan_features.invoices'),
            self::Expenses => __('billing.plan_features.expenses'),
            self::Chat => __('billing.plan_features.chat'),
        };
    }

    public function isRequired(): bool
    {
        return $this === self::Employees;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $feature) => $feature->value, self::cases());
    }
}
