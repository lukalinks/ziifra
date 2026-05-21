<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;

class DashboardQuickActions
{
    /**
     * @return list<array{route: string, label: string, icon: string, params?: array<string, mixed>}>
     */
    public static function for(
        User $user,
        Organization $organization,
        bool $canManageEmployees,
        bool $canViewLeave,
        bool $canRequestLeave,
        bool $canManageOrganization,
        bool $hasPayroll,
    ): array {
        $actions = [];

        if ($user->can('inviteMembers', $organization)) {
            $actions[] = [
                'route' => 'team.index',
                'label' => __('dashboard.invite_team'),
                'icon' => 'user-plus',
            ];
        }

        if ($canManageEmployees) {
            $actions[] = [
                'route' => 'employees.create',
                'label' => __('dashboard.add_employee'),
                'icon' => 'plus',
            ];
            $actions[] = [
                'route' => 'employees.import',
                'label' => __('dashboard.import_employees'),
                'icon' => 'upload',
            ];
        }

        if ($canRequestLeave) {
            $actions[] = [
                'route' => 'leave.create',
                'label' => __('dashboard.request_leave'),
                'icon' => 'calendar-plus',
            ];
        }

        if ($canViewLeave) {
            $actions[] = [
                'route' => 'leave.calendar',
                'label' => __('dashboard.view_calendar'),
                'icon' => 'calendar',
            ];
            $actions[] = [
                'route' => 'leave.index',
                'label' => __('dashboard.all_leave'),
                'icon' => 'list',
            ];
        }

        if ($hasPayroll) {
            $actions[] = [
                'route' => 'payroll.index',
                'label' => __('dashboard.payroll'),
                'icon' => 'currency',
            ];
        }

        if ($canManageOrganization) {
            $actions[] = [
                'route' => 'settings.index',
                'label' => __('dashboard.setup_company'),
                'icon' => 'settings',
            ];
        }

        return $actions;
    }
}
