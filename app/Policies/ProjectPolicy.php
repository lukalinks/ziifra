<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\CurrentOrganization;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToCurrentOrganization($user, $project) && $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $this->canManage($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
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

    protected function belongsToCurrentOrganization(User $user, Project $project): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $project->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
