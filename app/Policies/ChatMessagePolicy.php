<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;
use App\Support\CurrentOrganization;

class ChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user): bool
    {
        return $this->isMember($user);
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $message)) {
            return false;
        }

        $role = $user->roleIn($message->organization);

        return $message->user_id === $user->id
            || ($role?->canManageEmployees() ?? false);
    }

    protected function isMember(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null && $user->belongsToOrganization($organization);
    }

    protected function belongsToCurrentOrganization(User $user, ChatMessage $message): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $message->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
