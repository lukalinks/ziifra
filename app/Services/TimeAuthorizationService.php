<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TimeAuthorizationService
{
    public function __construct(
        protected EmployeeProfileService $profiles,
    ) {}

    public function canViewAll(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageEmployees() ?? false;
    }

    public function canClockOwn(User $user, Organization $organization): bool
    {
        if ($user->roleIn($organization) !== OrganizationRole::Employee) {
            return false;
        }

        return $this->profiles->employeeFor($user, $organization) !== null;
    }

    public function canViewTeam(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->usesTeamDashboard() ?? false;
    }

    public function ownsEntry(User $user, TimeEntry $entry): bool
    {
        $employee = $this->profiles->employeeFor($user, $entry->organization);

        return $employee !== null && $entry->employee_id === $employee->id;
    }

    public function canClockFor(User $user, Organization $organization, Employee $employee): bool
    {
        if ($this->canViewAll($user, $organization)) {
            return true;
        }

        if ($this->ownsEntry($user, new TimeEntry(['employee_id' => $employee->id, 'organization_id' => $organization->id]))) {
            return true;
        }

        if ($this->canViewTeam($user, $organization)) {
            $managerProfile = $this->profiles->employeeFor($user, $organization);

            return $managerProfile !== null && $employee->manager_id === $managerProfile->id;
        }

        return false;
    }

    public function canManageEntries(User $user, Organization $organization): bool
    {
        return $this->canViewAll($user, $organization);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    public function clockableEmployees(User $user, Organization $organization): \Illuminate\Support\Collection
    {
        if ($this->canViewAll($user, $organization)) {
            return Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        if ($this->canViewTeam($user, $organization)) {
            $managerProfile = $this->profiles->employeeFor($user, $organization);

            if ($managerProfile === null) {
                return collect();
            }

            return Employee::query()
                ->where(function ($query) use ($managerProfile): void {
                    $query->where('id', $managerProfile->id)
                        ->orWhere('manager_id', $managerProfile->id);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        $self = $this->profiles->employeeFor($user, $organization);

        return $self ? collect([$self]) : collect();
    }

    /**
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeVisibleTo(Builder $query, User $user, Organization $organization): Builder
    {
        if ($this->canViewAll($user, $organization)) {
            return $query;
        }

        if ($this->canViewTeam($user, $organization)) {
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

        if ($this->canClockOwn($user, $organization)) {
            $employee = $this->profiles->employeeFor($user, $organization);

            return $query->where('employee_id', $employee?->id ?? 0);
        }

        return $query->whereRaw('0 = 1');
    }
}
