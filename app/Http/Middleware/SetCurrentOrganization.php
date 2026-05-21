<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Services\EmployeeProfileService;
use App\Support\CurrentOrganization;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    private function bindOrganization(Request $request, Organization $organization): void
    {
        $request->session()->put('current_organization_id', $organization->id);
        CurrentOrganization::set($organization);
        Workspace::setUrlDefaults($organization);

        $user = $request->user();

        if ($user !== null && $user->roleIn($organization) === OrganizationRole::Employee) {
            app(EmployeeProfileService::class)->linkByEmail($user, $organization);
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        CurrentOrganization::clear();
        Workspace::clearUrlDefaults();

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $organization = $this->resolveRouteOrganization($request);

        if ($organization instanceof Organization) {
            if (! $user->belongsToOrganization($organization)) {
                abort(403);
            }

            $this->bindOrganization($request, $organization);

            return $next($request);
        }

        $organizationId = $request->session()->get('current_organization_id');

        if ($organizationId === null) {
            $organizationId = $user->organizations()->value('organizations.id');
        }

        if ($organizationId === null) {
            return $next($request);
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            $request->session()->forget('current_organization_id');

            return $next($request);
        }

        if (! $user->belongsToOrganization($organization)) {
            abort(403);
        }

        $this->bindOrganization($request, $organization);

        return $next($request);
    }

    private function resolveRouteOrganization(Request $request): ?Organization
    {
        $route = $request->route();

        if ($route === null || ! str_starts_with(ltrim((string) $route->uri(), '/'), Workspace::ROUTE_PREFIX.'/')) {
            return null;
        }

        $routeOrganization = $request->route('organization');

        if ($routeOrganization instanceof Organization) {
            return $routeOrganization;
        }

        if (! is_string($routeOrganization) || $routeOrganization === '') {
            return null;
        }

        return Organization::query()->where('slug', $routeOrganization)->first()
            ?? abort(404);
    }
}
