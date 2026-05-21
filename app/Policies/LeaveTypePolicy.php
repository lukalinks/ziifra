<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;
use App\Support\CurrentOrganization;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageLeave() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $leaveType->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageLeave() ?? false);
    }
}
