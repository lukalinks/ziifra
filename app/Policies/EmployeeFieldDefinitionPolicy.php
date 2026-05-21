<?php

namespace App\Policies;

use App\Models\EmployeeFieldDefinition;
use App\Models\User;
use App\Support\CurrentOrganization;

class EmployeeFieldDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageEmployeeFieldDefinitions() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, EmployeeFieldDefinition $definition): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $definition->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageEmployeeFieldDefinitions() ?? false);
    }
}
