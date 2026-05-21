<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExpenseAuthorizationService
{
    public function __construct(
        protected EmployeeProfileService $profiles,
    ) {}

    public function canViewAll(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageFinance() ?? false;
    }

    public function canSubmitOwn(User $user, Organization $organization): bool
    {
        if ($user->roleIn($organization) !== OrganizationRole::Employee) {
            return false;
        }

        return $this->profiles->employeeFor($user, $organization) !== null;
    }

    public function canCreateForOthers(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageFinance() ?? false;
    }

    public function ownsClaim(User $user, ExpenseClaim $claim): bool
    {
        $employee = $this->profiles->employeeFor($user, $claim->organization);

        return $employee !== null && $claim->employee_id === $employee->id;
    }

    public function canApprove(User $user, ExpenseClaim $claim): bool
    {
        if (! $claim->isPending()) {
            return false;
        }

        $organization = $claim->organization;
        $role = $user->roleIn($organization);

        if ($role?->canManageFinance()) {
            return $user->belongsToOrganization($organization);
        }

        if ($role === OrganizationRole::Manager) {
            return $this->managesEmployee($user, $organization, $claim->employee);
        }

        return false;
    }

    public function managesEmployee(User $user, Organization $organization, Employee $employee): bool
    {
        $managerProfile = $this->profiles->employeeFor($user, $organization);

        return $managerProfile !== null && $employee->manager_id === $managerProfile->id;
    }

    /**
     * @param  Builder<ExpenseClaim>  $query
     * @return Builder<ExpenseClaim>
     */
    public function scopeVisibleTo(Builder $query, User $user, Organization $organization): Builder
    {
        if ($this->canViewAll($user, $organization)) {
            return $query;
        }

        $role = $user->roleIn($organization);

        if ($role === OrganizationRole::Manager) {
            $managerProfile = $this->profiles->employeeFor($user, $organization);

            if ($managerProfile === null) {
                return $query->whereRaw('0 = 1');
            }

            $reportIds = Employee::query()
                ->where('organization_id', $organization->id)
                ->where('manager_id', $managerProfile->id)
                ->pluck('id');

            return $query->whereIn('employee_id', $reportIds->push($managerProfile->id));
        }

        if ($role === OrganizationRole::Employee) {
            $employee = $this->profiles->employeeFor($user, $organization);

            return $query->where('employee_id', $employee?->id ?? 0);
        }

        return $query->whereRaw('0 = 1');
    }
}
