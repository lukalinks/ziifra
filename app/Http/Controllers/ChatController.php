<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\Organization;
use App\Models\User;
use App\Services\ChatService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chat,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ChatMessage::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();
        $chatSettings = $organization->resolvedChatSettings();
        $privateEnabled = (bool) ($chatSettings['private_chat_enabled'] ?? true);

        $withUserId = $request->integer('with') ?: null;
        $activePartner = null;

        if ($withUserId && $privateEnabled && $this->chat->userBelongsToOrganization($organization, $withUserId)) {
            $activePartner = User::query()->find($withUserId);
            $messages = $this->chat->paginatedDirectMessages($user->id, $withUserId);
        } else {
            $messages = $this->chat->paginatedTeamMessages();
        }

        $role = $user->roleIn($organization);
        $canWrite = ($chatSettings['employees_can_write'] ?? true)
            || in_array($role, [OrganizationRole::Owner, OrganizationRole::Admin, OrganizationRole::Hr], true);

        return view('app.chat.index', [
            'organization' => $organization,
            'messages' => $messages,
            'canWrite' => $canWrite,
            'chatSettings' => $chatSettings,
            'privateEnabled' => $privateEnabled,
            'partners' => $privateEnabled ? $this->chat->privateChatPartners($organization, $user) : collect(),
            'activePartner' => $activePartner,
            'isTeamChannel' => $activePartner === null,
        ]);
    }

    public function store(StoreChatMessageRequest $request): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $settings = $organization->resolvedChatSettings();
        $role = $request->user()->roleIn($organization);

        if (! ($settings['employees_can_write'] ?? true)
            && ! in_array($role, [OrganizationRole::Owner, OrganizationRole::Admin, OrganizationRole::Hr], true)) {
            abort(403, __('chat.employees_cannot_write'));
        }

        $validated = $request->validated();
        $recipientId = $validated['recipient_user_id'] ?? null;
        if ($recipientId !== null) {
            $recipientId = (int) $recipientId;
        }

        if ($recipientId !== null) {
            if (! ($settings['private_chat_enabled'] ?? true)) {
                abort(403, __('chat.private_disabled'));
            }

            if (! $this->chat->userBelongsToOrganization($organization, (int) $recipientId)) {
                abort(422);
            }
        }

        ChatMessage::query()->create([
            'user_id' => $request->user()->id,
            'recipient_user_id' => $recipientId,
            'body' => $validated['body'],
        ]);

        $params = $recipientId ? ['with' => $recipientId] : [];

        return redirect()
            ->route('chat.index', $params)
            ->with('status', __('chat.sent'));
    }

    public function destroy(Organization $organization, ChatMessage $chatMessage): RedirectResponse
    {
        $this->authorize('delete', $chatMessage);

        if (! $this->chat->canAccessMessage(auth()->user(), $chatMessage)) {
            abort(403);
        }

        $redirectParams = $chatMessage->isDirectMessage()
            ? ['with' => $chatMessage->user_id === auth()->id()
                ? $chatMessage->recipient_user_id
                : $chatMessage->user_id]
            : [];

        $chatMessage->delete();

        return redirect()
            ->route('chat.index', $redirectParams)
            ->with('status', __('chat.deleted'));
    }
}
