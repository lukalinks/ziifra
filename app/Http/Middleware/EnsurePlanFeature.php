<?php

namespace App\Http\Middleware;

use App\Enums\PlanFeature;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            abort(403);
        }

        $planFeature = PlanFeature::tryFrom($feature);

        if ($planFeature === null) {
            abort(500, "Unknown plan feature [{$feature}].");
        }

        if (! app(OrganizationBillingService::class)->hasFeature($organization, $planFeature)) {
            return redirect()
                ->to(Workspace::route('settings.billing', $organization).'#plans')
                ->with('error', __('billing.feature_upgrade_required', [
                    'feature' => $planFeature->label(),
                ]));
        }

        return $next($request);
    }
}
