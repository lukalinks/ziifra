<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;
use App\Support\CurrentOrganization;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageEmployees() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Position $position): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $position->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageEmployees() ?? false);
    }
}
