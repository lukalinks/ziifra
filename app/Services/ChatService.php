<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ChatService
{
    /**
     * @return Collection<int, User>
     */
    public function privateChatPartners(Organization $organization, User $currentUser): Collection
    {
        return $organization->users()
            ->where('users.id', '!=', $currentUser->id)
            ->orderBy('users.name')
            ->get();
    }

    public function teamMessagesQuery(): Builder
    {
        return ChatMessage::query()
            ->whereNull('recipient_user_id')
            ->with(['user', 'recipient']);
    }

    public function directMessagesQuery(int $userId, int $otherUserId): Builder
    {
        return ChatMessage::query()
            ->directBetween($userId, $otherUserId)
            ->with(['user', 'recipient']);
    }

    public function paginatedTeamMessages(): LengthAwarePaginator
    {
        return $this->teamMessagesQuery()
            ->orderBy('created_at')
            ->paginate(50)
            ->withQueryString();
    }

    public function paginatedDirectMessages(int $userId, int $otherUserId): LengthAwarePaginator
    {
        return $this->directMessagesQuery($userId, $otherUserId)
            ->orderBy('created_at')
            ->paginate(50)
            ->withQueryString();
    }

    public function userBelongsToOrganization(Organization $organization, int $userId): bool
    {
        return $organization->users()->where('users.id', $userId)->exists();
    }

    public function canAccessMessage(User $user, ChatMessage $message): bool
    {
        if ($message->recipient_user_id === null) {
            return true;
        }

        return $message->user_id === $user->id
            || $message->recipient_user_id === $user->id;
    }
}
