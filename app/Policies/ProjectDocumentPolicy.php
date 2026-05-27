<?php

namespace App\Policies;

use App\Models\ProjectDocument;
use App\Models\User;
use App\Support\CurrentOrganization;

class ProjectDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, ProjectDocument $document): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $document->organization_id === $organization->id
            && $this->canManage($user);
    }

    protected function canView(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && ($user->roleIn($organization)?->canViewEmployees() ?? false);
    }

    protected function canManage(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && ($user->roleIn($organization)?->canManageEmployees() ?? false);
    }
}
