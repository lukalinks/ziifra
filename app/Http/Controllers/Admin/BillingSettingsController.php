<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Services\AdminAuditService;
use App\Services\BillingConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingSettingsController extends Controller
{
    public function edit(BillingConfigurationService $billing): View
    {
        return view('admin.billing.edit', [
            'trialDays' => $billing->trialDays(),
            'plans' => $billing->plans(),
            'planKeys' => $billing->configurablePlanKeys(),
            'featureCatalog' => $billing->featureCatalog(),
        ]);
    }

    public function update(
        Request $request,
        BillingConfigurationService $billing,
        AdminAuditService $audit,
    ): RedirectResponse {
        $validated = $request->validate([
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'plans' => ['required', 'array'],
            'plans.*.name' => ['required', 'string', 'max:100'],
            'plans.*.employee_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'plans.*.price_label' => ['required', 'string', 'max:120'],
            'plans.*.monthly_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'plans.*.stripe_price_id' => ['nullable', 'string', 'max:255'],
            'plans.*.paypal_plan_id' => ['nullable', 'string', 'max:255'],
            'plans.*.enabled_features' => ['nullable', 'array'],
            'plans.*.enabled_features.*' => ['string', Rule::in(PlanFeature::values())],
        ]);

        $billing->update(
            (int) $validated['trial_days'],
            $validated['plans'],
            $request->user(),
        );

        $audit->log(
            $request->user(),
            'platform.billing_updated',
            metadata: [
                'trial_days' => (int) $validated['trial_days'],
                'plans' => array_keys($validated['plans']),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.billing.edit')
            ->with('status', __('admin.billing.saved'));
    }
}
