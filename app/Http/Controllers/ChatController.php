<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ChatMessage::class);

        $messages = ChatMessage::query()
            ->with('user')
            ->orderBy('created_at')
            ->paginate(50);

        return view('app.chat.index', [
            'organization' => CurrentOrganization::check(),
            'messages' => $messages,
        ]);
    }

    public function store(StoreChatMessageRequest $request): RedirectResponse
    {
        ChatMessage::query()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('chat.index')
            ->with('status', __('chat.sent'));
    }

    public function destroy(Organization $organization, ChatMessage $chatMessage): RedirectResponse
    {
        $this->authorize('delete', $chatMessage);

        $chatMessage->delete();

        return redirect()
            ->route('chat.index')
            ->with('status', __('chat.deleted'));
    }
}
