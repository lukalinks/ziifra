<?php

namespace App\Support;

use App\Enums\OrganizationRole;
use App\Enums\PlanFeature;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkspaceNavItem;
use App\Services\EmployeeProfileService;
use App\Services\OrganizationBillingService;

class WorkspaceNavigation
{
    public function __construct(
        protected OrganizationBillingService $billing,
    ) {}

    /**
     * @return list<array{label: string, items: list<array<string, mixed>>}>
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

        $primaryItems = [
            $this->link(__('navigation.dashboard'), 'dashboard', request()->routeIs('dashboard')),
        ];

        if ($role === OrganizationRole::Employee && $linkedEmployee !== null) {
            $primaryItems[] = $this->link(
                __('navigation.my_profile'),
                'employees.show',
                request()->routeIs('employees.show')
                    && (int) request()->route('employee')?->getKey() === $linkedEmployee->id,
                __('employee_dashboard.shortcut_profile'),
                route('employees.show', [$organization, $linkedEmployee]),
            );
        }

        if ($role->canViewEmployees()) {
            $primaryItems[] = $this->link(
                __('navigation.employees'),
                'employees.index',
                request()->routeIs('employees.*'),
            );
        }

        if ($role->canViewEmployees() && $this->billing->hasFeature($organization, PlanFeature::Projects)) {
            $primaryItems[] = $this->link(
                __('navigation.projects'),
                'projects.index',
                request()->routeIs('projects.*'),
            );
        }

        if ($role->canViewEmployees() && $this->billing->hasFeature($organization, PlanFeature::Documents)) {
            $primaryItems[] = $this->link(
                __('navigation.hr_documents'),
                'documents.index',
                request()->routeIs('documents.*'),
            );
        }

        if ($role->canManageEmployees() && $this->billing->hasFeature($organization, PlanFeature::Payroll)) {
            $primaryItems[] = $this->link(
                __('navigation.payroll_and_time'),
                'payroll-time.index',
                request()->routeIs('payroll-time.*'),
            );
        }

        if ($role->canManageFinance() && $this->billing->hasFeature($organization, PlanFeature::Invoices)) {
            $primaryItems[] = $this->link(
                __('navigation.invoices'),
                'invoices.index',
                request()->routeIs('invoices.*'),
            );
        }

        foreach (WorkspaceNavItem::query()->orderBy('sort_order')->get() as $customItem) {
            $primaryItems[] = [
                'label' => $customItem->label,
                'route' => null,
                'href' => $customItem->url,
                'active' => false,
                'enabled' => true,
                'coming_soon' => false,
            ];
        }

        $this->pushGroup($groups, __('navigation.primary'), $primaryItems);

        $peopleItems = [];

        if ($this->billing->hasFeature($organization, PlanFeature::Leave)
            && ($role->canViewAllLeave()
                || ($role->canRequestOwnLeave() && $linkedEmployee !== null))) {
            $peopleItems[] = $this->link(
                __('navigation.leave'),
                'leave.index',
                request()->routeIs('leave.*'),
                __('employee_dashboard.shortcut_leave'),
            );
        }

        if ($role->canRequestOwnLeave() && $linkedEmployee !== null) {
            if ($this->billing->hasFeature($organization, PlanFeature::TimeTracking)) {
                $peopleItems[] = $this->link(
                    __('navigation.time_and_attendance'),
                    'time.index',
                    request()->routeIs('time.*'),
                    __('employee_dashboard.shortcut_time'),
                );
            }

            if ($this->billing->hasFeature($organization, PlanFeature::Expenses)) {
                $peopleItems[] = $this->link(
                    __('navigation.expenses'),
                    'expenses.index',
                    request()->routeIs('expenses.*'),
                    __('employee_dashboard.shortcut_expenses'),
                );
            }
        }

        $this->pushGroup($groups, __('navigation.people'), $peopleItems);

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

        $chatSettings = $organization->resolvedChatSettings();
        if ($this->billing->hasFeature($organization, PlanFeature::Chat)
            && ($chatSettings['enabled'] ?? true)) {
            $this->pushGroup($groups, __('navigation.collaborate'), [
                $this->link(
                    __('navigation.chat'),
                    'chat.index',
                    request()->routeIs('chat.*'),
                    __('employee_dashboard.shortcut_chat'),
                ),
            ]);
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
     * @return list<array<string, mixed>>
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
     * @return list<array<string, mixed>>
     */
    public function primaryMobile(?Organization $organization, ?User $user, int $limit = 3): array
    {
        $flat = $this->flat($organization, $user);

        if ($flat === []) {
            return [];
        }

        $priorityRoutes = [
            'dashboard',
            'employees.show',
            'employees.index',
            'projects.index',
            'payroll-time.index',
            'leave.index',
            'time.index',
            'expenses.index',
            'chat.index',
            'documents.index',
            'invoices.index',
            'reports.index',
            'settings.index',
            'team.index',
        ];

        $picked = [];
        $pickedRoutes = [];

        foreach ($priorityRoutes as $route) {
            if (count($picked) >= $limit) {
                break;
            }

            foreach ($flat as $item) {
                if (($item['route'] ?? null) === $route && ! in_array($route, $pickedRoutes, true)) {
                    $picked[] = $item;
                    $pickedRoutes[] = $route;

                    break;
                }
            }
        }

        foreach ($flat as $item) {
            if (count($picked) >= $limit) {
                break;
            }

            $route = $item['route'] ?? null;

            if ($route !== null && ! in_array($route, $pickedRoutes, true)) {
                $picked[] = $item;
                $pickedRoutes[] = $route;
            }
        }

        return $picked;
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
     * @return array<string, mixed>
     */
    protected function link(
        string $label,
        string $route,
        bool $active,
        ?string $hint = null,
        ?string $href = null,
    ): array {
        return [
            'label' => $label,
            'route' => $route,
            'href' => $href,
            'hint' => $hint,
            'active' => $active,
            'enabled' => true,
            'coming_soon' => false,
        ];
    }
}
