<?php

namespace App\Policies;

use App\Models\DocumentFolder;
use App\Models\User;
use App\Support\CurrentOrganization;

class DocumentFolderPolicy
{
    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $user->roleIn($organization)?->canManageEmployees() ?? false;
    }

    public function delete(User $user, DocumentFolder $folder): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $folder->organization_id === $organization->id
            && ($user->roleIn($organization)?->canManageEmployees() ?? false);
    }
}
