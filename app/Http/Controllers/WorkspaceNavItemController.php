<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceNavItemRequest;
use App\Models\Organization;
use App\Models\WorkspaceNavItem;
use App\Services\WorkspaceNavItemService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;

class WorkspaceNavItemController extends Controller
{
    public function store(StoreWorkspaceNavItemRequest $request, WorkspaceNavItemService $nav): RedirectResponse
    {
        $nav->create(
            CurrentOrganization::check(),
            $request->user(),
            $request->validated(),
        );

        return back()->with('status', __('navigation.custom_created'));
    }

    public function destroy(Organization $organization, WorkspaceNavItem $navItem, WorkspaceNavItemService $nav): RedirectResponse
    {
        $this->authorize('delete', $navItem);

        $nav->delete($navItem);

        return back()->with('status', __('navigation.custom_deleted'));
    }
}
