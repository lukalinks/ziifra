<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\CurrentOrganization;

class DailyHoursEntryPolicy
{
    public function manage(User $user, Project $project): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $project->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageEmployees() ?? false);
    }
}
