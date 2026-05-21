<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;

class ReportAuthorizationService
{
    public function __construct(
        protected EmployeeProfileService $profiles,
    ) {}

    public function canView(User $user, Organization $organization): bool
    {
        $role = $user->roleIn($organization);

        if ($role === null) {
            return false;
        }

        return $role->canViewAllLeave() || $role->canManageEmployees();
    }

    public function hasFullAccess(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageEmployees() ?? false;
    }

    public function canViewFinance(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageFinance() ?? false;
    }

    /**
     * @return list<int>|null null means entire organization
     */
    public function scopedEmployeeIds(User $user, Organization $organization): ?array
    {
        if ($this->hasFullAccess($user, $organization)) {
            return null;
        }

        $managerProfile = $this->profiles->employeeFor($user, $organization);

        if ($managerProfile === null) {
            return [];
        }

        $ids = Employee::query()
            ->where('manager_id', $managerProfile->id)
            ->pluck('id')
            ->all();

        $ids[] = $managerProfile->id;

        return $ids;
    }
}
