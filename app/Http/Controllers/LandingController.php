<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use App\Services\BillingConfigurationService;

class LandingController extends Controller
{
    public function __invoke(BillingConfigurationService $billing)
    {
        return view('landing', [
            'pricingPlans' => [
                SubscriptionPlan::Starter->value => $billing->plan(SubscriptionPlan::Starter->value),
                SubscriptionPlan::Pro->value => $billing->plan(SubscriptionPlan::Pro->value),
            ],
            'trialDays' => $billing->trialDays(),
        ]);
    }
}
