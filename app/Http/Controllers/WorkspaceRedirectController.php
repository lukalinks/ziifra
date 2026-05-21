<?php

namespace App\Http\Controllers;

use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceRedirectController extends Controller
{
    public function dashboard(Request $request): RedirectResponse
    {
        return Workspace::redirectFromSession($request, 'dashboard');
    }

    public function team(Request $request): RedirectResponse
    {
        return Workspace::redirectFromSession($request, 'team.index');
    }

    public function settings(Request $request, ?string $path = null): RedirectResponse
    {
        $organization = Workspace::resolveFromRequest($request);
        $suffix = $path ? '/'.ltrim($path, '/') : '';

        return redirect('/'.Workspace::ROUTE_PREFIX.'/'.$organization->slug.'/settings'.$suffix);
    }

    public function employees(Request $request, ?string $path = null): RedirectResponse
    {
        $organization = Workspace::resolveFromRequest($request);
        $suffix = $path ? '/'.ltrim($path, '/') : '';

        return redirect('/'.Workspace::ROUTE_PREFIX.'/'.$organization->slug.'/employees'.$suffix);
    }

    public function leave(Request $request, ?string $path = null): RedirectResponse
    {
        $organization = Workspace::resolveFromRequest($request);
        $suffix = $path ? '/'.ltrim($path, '/') : '';

        return redirect('/'.Workspace::ROUTE_PREFIX.'/'.$organization->slug.'/leave'.$suffix);
    }
}
