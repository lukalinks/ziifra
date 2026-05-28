<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'recipient_user_id',
        'body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isTeamMessage(): bool
    {
        return $this->recipient_user_id === null;
    }

    public function isDirectMessage(): bool
    {
        return $this->recipient_user_id !== null;
    }

    /**
     * @param  Builder<ChatMessage>  $query
     */
    public function scopeTeam(Builder $query): Builder
    {
        return $query->whereNull('recipient_user_id');
    }

    /**
     * @param  Builder<ChatMessage>  $query
     */
    public function scopeDirectBetween(Builder $query, int $userId, int $otherUserId): Builder
    {
        return $query->where(function (Builder $query) use ($userId, $otherUserId): void {
            $query->where(function (Builder $query) use ($userId, $otherUserId): void {
                $query->where('user_id', $userId)
                    ->where('recipient_user_id', $otherUserId);
            })->orWhere(function (Builder $query) use ($userId, $otherUserId): void {
                $query->where('user_id', $otherUserId)
                    ->where('recipient_user_id', $userId);
            });
        });
    }
}
