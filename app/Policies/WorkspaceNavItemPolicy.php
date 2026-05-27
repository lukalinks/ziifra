<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceNavItem;
use App\Support\CurrentOrganization;

class WorkspaceNavItemPolicy
{
    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && ($user->roleIn($organization)?->canManageOrganization() ?? false);
    }

    public function delete(User $user, WorkspaceNavItem $item): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $item->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageOrganization() ?? false);
    }
}
