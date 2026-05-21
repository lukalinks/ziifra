<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSelectController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.organizations.select', [
            'organizations' => $request->user()->organizations()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $organization = Organization::query()->findOrFail($validated['organization_id']);

        if (! $request->user()->belongsToOrganization($organization)) {
            abort(403);
        }

        $request->session()->put('current_organization_id', $organization->id);

        return Workspace::redirect('dashboard', $organization);
    }
}
