<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->belongsToOrganization($organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        $role = $user->roleIn($organization);

        return $role?->canManageOrganization() ?? false;
    }

    public function inviteMembers(User $user, Organization $organization): bool
    {
        $role = $user->roleIn($organization);

        if ($role === null) {
            return false;
        }

        if ($organization->hrCanInvite()) {
            return $role->canInvite();
        }

        return $role->canManageOrganization();
    }
}
