<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Services\InvitationService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvitationAcceptController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::query()->where('token', $token)->firstOrFail();

        $existingUser = auth()->check();

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'needsAccount' => ! \App\Models\User::query()->where('email', $invitation->email)->exists(),
            'existingUser' => $existingUser,
            'invalid' => ! $invitation->isPending(),
        ]);
    }

    public function store(Request $request, string $token, InvitationService $service): RedirectResponse
    {
        $invitation = Invitation::query()->where('token', $token)->firstOrFail();

        $needsAccount = ! \App\Models\User::query()->where('email', $invitation->email)->exists();

        $rules = [];
        if ($needsAccount) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } elseif (! Auth::check()) {
            $rules['password'] = ['required'];
        }

        $validated = $request->validate($rules);

        if (! Auth::check() && ! $needsAccount) {
            if (! Auth::attempt(['email' => $invitation->email, 'password' => $validated['password'] ?? ''])) {
                return back()->withErrors(['password' => 'Invalid password.']);
            }
        }

        $result = $service->accept(
            $invitation,
            $validated['name'] ?? null,
            $validated['password'] ?? null,
        );

        Auth::login($result['user']);
        $request->session()->put('current_organization_id', $result['organization']->id);

        return Workspace::redirect('dashboard', $result['organization'])
            ->with('status', 'Welcome to '.$result['organization']->name.'!');
    }
}
