<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeProfileService;
use App\Support\CurrentOrganization;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canViewEmployees() ?? false;
    }

    public function view(User $user, Employee $employee): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $employee)) {
            return false;
        }

        $organization = CurrentOrganization::check();

        if ($user->roleIn($organization)?->canViewEmployees() ?? false) {
            return true;
        }

        $linked = app(EmployeeProfileService::class)->employeeFor($user, $organization);

        return $linked !== null && $linked->is($employee);
    }

    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageEmployees() ?? false;
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->belongsToCurrentOrganization($user, $employee)
            && ($user->roleIn(CurrentOrganization::check())?->canManageEmployees() ?? false);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }

    public function activateLogin(User $user, Employee $employee): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $this->update($user, $employee)
            && $user->can('inviteMembers', $organization);
    }

    protected function belongsToCurrentOrganization(User $user, Employee $employee): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $employee->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
