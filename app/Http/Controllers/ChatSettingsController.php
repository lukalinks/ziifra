<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateChatSettingsRequest;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatSettingsController extends Controller
{
    public function edit(): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        return view('app.settings.chat', [
            'organization' => $organization,
            'chatSettings' => $organization->resolvedChatSettings(),
        ]);
    }

    public function update(UpdateChatSettingsRequest $request): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $organization->update([
            'chat_settings' => $request->validated('chat_settings'),
        ]);

        return redirect()
            ->route('settings.chat.edit')
            ->with('status', __('settings.chat.saved'));
    }
}
