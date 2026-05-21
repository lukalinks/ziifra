<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ImpersonationController extends Controller
{
    public function store(Request $request, Organization $organization, AdminAuditService $audit): RedirectResponse
    {
        $owner = $organization->owner;

        if ($owner === null) {
            return back()->with('error', __('admin.no_owner'));
        }

        return $this->start($request, $owner, $organization, $audit);
    }

    public function storeUser(Request $request, User $user, AdminAuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => [
                'required',
                Rule::exists('organizations', 'id'),
            ],
        ]);

        $organization = Organization::query()->findOrFail($validated['organization_id']);

        if (! $user->belongsToOrganization($organization)) {
            return back()->with('error', 'User is not a member of that organization.');
        }

        return $this->start($request, $user, $organization, $audit);
    }

    public function destroy(Request $request, AdminAuditService $audit): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');

        if ($adminId === null) {
            return redirect()->route('admin.dashboard');
        }

        $admin = User::query()->find($adminId);

        if ($admin === null || ! $admin->isSuperAdmin()) {
            Auth::logout();

            return redirect()->route('login');
        }

        $impersonated = $request->user();

        $audit->log(
            $admin,
            'impersonation.ended',
            metadata: ['impersonated_user_id' => $impersonated?->id],
            request: $request,
        );

        Auth::login($admin);
        $request->session()->forget('current_organization_id');

        return redirect()->route('admin.organizations.index')
            ->with('status', __('admin.impersonation_ended'));
    }

    private function start(
        Request $request,
        User $target,
        Organization $organization,
        AdminAuditService $audit,
    ): RedirectResponse {
        if ($target->isSuperAdmin()) {
            return back()->with('error', __('admin.cannot_impersonate_super_admin'));
        }

        $admin = $request->user();

        $audit->log(
            $admin,
            'impersonation.started',
            $organization,
            $target,
            request: $request,
        );

        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('current_organization_id', $organization->id);

        Auth::login($target);

        return redirect()->to(Workspace::route('dashboard', $organization));
    }
}
