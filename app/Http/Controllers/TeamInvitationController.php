<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Services\InvitationService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamInvitationController extends Controller
{
    public function index(Request $request): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('inviteMembers', $organization);

        return view('app.team.index', [
            'organization' => $organization,
            'members' => $organization->users()->orderBy('name')->get(),
            'invitations' => $organization->invitations()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get(),
            'roles' => [
                OrganizationRole::Admin,
                OrganizationRole::Hr,
                OrganizationRole::Manager,
                OrganizationRole::Employee,
            ],
        ]);
    }

    public function store(Request $request, InvitationService $service): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('inviteMembers', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,hr,manager,employee'],
        ]);

        $service->send(
            $organization,
            $request->user(),
            $validated['email'],
            OrganizationRole::from($validated['role']),
        );

        return back()->with('status', 'Invitation sent successfully.');
    }

    public function destroy(Organization $organization, Invitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        $invitation->delete();

        return back()->with('status', 'Invitation cancelled.');
    }
}
