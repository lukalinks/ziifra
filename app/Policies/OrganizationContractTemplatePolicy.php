<?php

namespace App\Policies;

use App\Models\OrganizationContractTemplate;
use App\Models\User;
use App\Support\CurrentOrganization;

class OrganizationContractTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageOrganization() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, OrganizationContractTemplate $template): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $template->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageOrganization() ?? false);
    }

    public function delete(User $user, OrganizationContractTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}
