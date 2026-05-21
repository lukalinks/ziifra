<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;

class EmployeeProfileService
{
    public function employeeFor(User $user, Organization $organization): ?Employee
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function linkByEmail(User $user, Organization $organization): ?Employee
    {
        if ($user->email === null || $user->email === '') {
            return null;
        }

        $employee = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('email', $user->email)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->first();

        if ($employee === null) {
            return null;
        }

        if ($employee->user_id !== $user->id) {
            $employee->update(['user_id' => $user->id]);
        }

        return $employee->fresh();
    }

    public function linkAfterInvitation(User $user, Organization $organization, OrganizationRole $role): ?Employee
    {
        if ($role !== OrganizationRole::Employee) {
            return $this->linkByEmail($user, $organization);
        }

        return $this->linkByEmail($user, $organization);
    }
}
