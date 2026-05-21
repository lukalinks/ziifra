<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\Support\CurrentOrganization;

class InvitationPolicy
{
    public function delete(User $user, Invitation $invitation): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $invitation->organization_id === $organization->id
            && (new OrganizationPolicy)->inviteMembers($user, $organization);
    }
}
