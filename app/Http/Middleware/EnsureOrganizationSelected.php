<?php

namespace App\Http\Middleware;

use App\Support\CurrentOrganization;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CurrentOrganization::get() === null) {
            $user = $request->user();
            $organizations = $user?->organizations()->orderBy('name')->get();

            if ($organizations === null || $organizations->isEmpty()) {
                return redirect()->route('register')
                    ->with('error', 'You are not assigned to any organization.');
            }

            if ($organizations->count() === 1) {
                return Workspace::redirect('dashboard', $organizations->first());
            }

            return redirect()->route('organizations.select');
        }

        return $next($request);
    }
}
