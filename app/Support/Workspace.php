<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Workspace
{
    public const ROUTE_PREFIX = 'o';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, Organization|string|null $organization = null, array $parameters = [], bool $absolute = true): string
    {
        $parameters = array_merge(self::organizationParameter($organization), $parameters);

        return route($name, $parameters, $absolute);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function redirect(string $name, Organization|string|null $organization = null, array $parameters = []): RedirectResponse
    {
        return redirect()->to(self::route($name, $organization, $parameters));
    }

    public static function resolveFromRequest(Request $request, ?User $user = null): Organization
    {
        $user ??= $request->user();

        if ($user === null) {
            abort(403);
        }

        $routeOrganization = $request->route('organization');

        if ($routeOrganization instanceof Organization) {
            if (! $user->belongsToOrganization($routeOrganization)) {
                abort(403);
            }

            return $routeOrganization;
        }

        if ($id = $request->session()->get('current_organization_id')) {
            $organization = Organization::query()->find($id);

            if ($organization !== null && $user->belongsToOrganization($organization)) {
                return $organization;
            }
        }

        $organizations = $user->organizations()->orderBy('name')->get();

        if ($organizations->count() === 1) {
            return $organizations->first();
        }

        if ($organizations->isEmpty()) {
            abort(403, 'You are not assigned to any organization.');
        }

        throw new HttpResponseException(
            redirect()->route('organizations.select')
        );
    }

    public static function redirectFromSession(Request $request, string $route = 'dashboard', array $parameters = []): RedirectResponse
    {
        return self::redirect($route, self::resolveFromRequest($request), $parameters);
    }

    public static function setUrlDefaults(Organization $organization): void
    {
        url()->defaults(['organization' => $organization->slug]);
    }

    public static function clearUrlDefaults(): void
    {
        url()->defaults([]);
    }

    /**
     * @return array{organization: string}
     */
    private static function organizationParameter(Organization|string|null $organization): array
    {
        if ($organization === null) {
            $organization = CurrentOrganization::get();
        }

        if ($organization instanceof Organization) {
            return ['organization' => $organization->slug];
        }

        if (is_string($organization) && $organization !== '') {
            return ['organization' => $organization];
        }

        return [];
    }
}
