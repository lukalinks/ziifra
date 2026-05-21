<?php

namespace App\Support;

use App\Enums\OrganizationRole;
use App\Enums\PlanFeature;
use App\Models\Organization;
use App\Models\User;
use App\Services\EmployeeProfileService;
use App\Services\OrganizationBillingService;

class WorkspaceNavigation
{
    public function __construct(
        protected OrganizationBillingService $billing,
    ) {}

    /**
     * Grouped workspace navigation for the app sidebar.
     *
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         label: string,
     *         route: string|null,
     *         active: bool,
     *         enabled: bool,
     *         coming_soon: bool,
     *     }>
     * }>
     */
    public function groups(?Organization $organization, ?User $user): array
    {
        if ($organization === null || $user === null) {
            return [];
        }

        $role = $user->roleIn($organization);

        if ($role === null) {
            return [];
        }

        $profiles = app(EmployeeProfileService::class);
        $linkedEmployee = $profiles->employeeFor($user, $organization);

        $groups = [];

        $groups[] = [
            'label' => __('navigation.overview'),
            'items' => [
                $this->link(__('navigation.dashboard'), 'dashboard', request()->routeIs('dashboard')),
            ],
        ];

        $peopleItems = [];

        if ($role->canViewEmployees()) {
            $peopleItems[] = $this->link(
                __('navigation.employees'),
                'employees.index',
                request()->routeIs('employees.*'),
            );

            if ($this->billing->hasFeature($organization, PlanFeature::Documents)) {
                $peopleItems[] = $this->link(
                    __('navigation.documents'),
                    'documents.index',
                    request()->routeIs('documents.*'),
                );
            }
        }

        if ($this->billing->hasFeature($organization, PlanFeature::Leave)
            && ($role->canViewAllLeave()
                || ($role->canRequestOwnLeave() && $linkedEmployee !== null))) {
            $peopleItems[] = $this->link(
                __('navigation.leave'),
                'leave.index',
                request()->routeIs('leave.*'),
            );
        }

        $this->pushGroup($groups, __('navigation.people'), $peopleItems);

        $payItems = [];

        if ($role->canManageEmployees() && $this->billing->hasFeature($organization, PlanFeature::Payroll)) {
            $payItems[] = $this->link(
                __('navigation.payroll'),
                'payroll.index',
                request()->routeIs('payroll.*'),
            );
        }

        if ($role->canManageFinance() && $this->billing->hasFeature($organization, PlanFeature::Invoices)) {
            $payItems[] = $this->link(
                __('navigation.invoices'),
                'invoices.index',
                request()->routeIs('invoices.*'),
            );
        }

        if ($this->billing->hasFeature($organization, PlanFeature::Expenses)
            && ($role->canManageFinance()
            || $role->usesTeamDashboard()
            || ($role->canRequestOwnLeave() && $linkedEmployee !== null))) {
            $payItems[] = $this->link(
                __('navigation.expenses'),
                'expenses.index',
                request()->routeIs('expenses.*'),
            );
        }

        $this->pushGroup($groups, __('navigation.pay_and_finance'), $payItems);

        $workItems = [];

        if ($role->canViewEmployees() && $this->billing->hasFeature($organization, PlanFeature::Projects)) {
            $workItems[] = $this->link(
                __('navigation.projects'),
                'projects.index',
                request()->routeIs('projects.*'),
            );
        }

        if ($this->billing->hasFeature($organization, PlanFeature::TimeTracking)
            && ($role->canViewEmployees()
            || $role->usesTeamDashboard()
            || ($role->canRequestOwnLeave() && $linkedEmployee !== null))) {
            $workItems[] = $this->link(
                __('navigation.time_and_attendance'),
                'time.index',
                request()->routeIs('time.*'),
            );
        }

        $this->pushGroup($groups, __('navigation.work'), $workItems);

        $insightItems = [];

        if (($role->canViewAllLeave() || $role->canManageEmployees())
            && $this->billing->hasFeature($organization, PlanFeature::Reports)) {
            $insightItems[] = $this->link(
                __('navigation.reports'),
                'reports.index',
                request()->routeIs('reports.*'),
            );
        }

        $this->pushGroup($groups, __('navigation.insights'), $insightItems);

        if ($this->billing->hasFeature($organization, PlanFeature::Chat)) {
            $collaborateItems = [
                $this->link(
                    __('navigation.chat'),
                    'chat.index',
                    request()->routeIs('chat.*'),
                ),
            ];

            $this->pushGroup($groups, __('navigation.collaborate'), $collaborateItems);
        }

        $adminItems = [];

        if ($role->canManageEmployees() || $role->canManageOrganization()) {
            $adminItems[] = $this->link(
                __('navigation.settings'),
                'settings.index',
                request()->routeIs('settings.*'),
            );
        }

        if ($role->canInvite() && $this->billing->hasFeature($organization, PlanFeature::TeamInvitations)) {
            $adminItems[] = $this->link(
                __('navigation.team'),
                'team.index',
                request()->routeIs('team.*'),
            );
        }

        $this->pushGroup($groups, __('navigation.admin'), $adminItems);

        return $groups;
    }

    /**
     * Flat list of enabled links (for mobile horizontal nav).
     *
     * @return list<array{label: string, route: string, active: bool, enabled: bool, coming_soon: bool}>
     */
    public function flat(?Organization $organization, ?User $user): array
    {
        $items = [];

        foreach ($this->groups($organization, $user) as $group) {
            foreach ($group['items'] as $item) {
                if ($item['enabled']) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  list<array<string, mixed>>  $items
     */
    protected function pushGroup(array &$groups, string $label, array $items): void
    {
        if ($items === []) {
            return;
        }

        $groups[] = [
            'label' => $label,
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, route: string, active: bool, enabled: bool, coming_soon: bool}
     */
    protected function link(string $label, string $route, bool $active): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'active' => $active,
            'enabled' => true,
            'coming_soon' => false,
        ];
    }

    /**
     * @return array{label: string, route: null, active: bool, enabled: bool, coming_soon: bool}
     */
    protected function comingSoon(string $label): array
    {
        return [
            'label' => $label,
            'route' => null,
            'active' => false,
            'enabled' => false,
            'coming_soon' => true,
        ];
    }
}
