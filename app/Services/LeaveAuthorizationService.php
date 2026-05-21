<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeaveAuthorizationService
{
    public function __construct(
        protected EmployeeProfileService $profiles,
    ) {}

    public function canViewAll(User $user, Organization $organization): bool
    {
        $role = $user->roleIn($organization);

        return in_array($role, [OrganizationRole::Owner, OrganizationRole::Admin, OrganizationRole::Hr], true);
    }

    public function canRequestOwn(User $user, Organization $organization): bool
    {
        if ($user->roleIn($organization) !== OrganizationRole::Employee) {
            return false;
        }

        return $this->profiles->employeeFor($user, $organization) !== null;
    }

    public function canCreateForOthers(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageLeave() ?? false;
    }

    public function ownsRequest(User $user, LeaveRequest $leaveRequest): bool
    {
        $employee = $this->profiles->employeeFor($user, $leaveRequest->organization);

        return $employee !== null && $leaveRequest->employee_id === $employee->id;
    }

    public function canApprove(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $leaveRequest->isPending()) {
            return false;
        }

        $organization = $leaveRequest->organization;
        $role = $user->roleIn($organization);

        if ($role === null) {
            return false;
        }

        if (in_array($role, [OrganizationRole::Owner, OrganizationRole::Admin, OrganizationRole::Hr], true)) {
            return $user->belongsToOrganization($organization);
        }

        if ($role === OrganizationRole::Manager) {
            return $this->managesEmployee($user, $organization, $leaveRequest->employee);
        }

        return false;
    }

    public function managesEmployee(User $user, Organization $organization, Employee $employee): bool
    {
        $managerProfile = $this->profiles->employeeFor($user, $organization);

        return $managerProfile !== null && $employee->manager_id === $managerProfile->id;
    }

    /**
     * @param  Builder<LeaveRequest>  $query
     * @return Builder<LeaveRequest>
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
