<?php

namespace App\Http\Middleware;

use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayrollEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            abort(403);
        }

        if (! app(OrganizationBillingService::class)->hasPayroll($organization)) {
            return redirect()
                ->route('settings.billing')
                ->with('error', __('payroll.upgrade_required'));
        }

        return $next($request);
    }
}
