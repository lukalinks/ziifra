<?php

namespace App\Http\Middleware;

use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsActive
{
    public function __construct(
        private OrganizationBillingService $billing,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return $next($request);
        }

        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($this->billing->isSuspended($organization)) {
            abort(403, __('billing.suspended'));
        }

        if ($this->billing->trialExpired($organization)
            && ! $request->routeIs('settings.billing', 'settings.billing.*', 'logout')) {
            return redirect()
                ->to(\App\Support\Workspace::route('settings.billing', $organization))
                ->with('error', __('billing.trial_expired'));
        }

        return $next($request);
    }
}
