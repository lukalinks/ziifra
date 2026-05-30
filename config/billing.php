<?php

use App\Enums\PlanFeature;

return [

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /** Activate paid plans without Stripe (local/demo). Set BILLING_ALLOW_MANUAL_UPGRADE=true in .env */
    'allow_manual_upgrade' => filter_var(env('BILLING_ALLOW_MANUAL_UPGRADE', false), FILTER_VALIDATE_BOOL),

    /** Days before trial end to email owners/admins (also sends on day 0). */
    'trial_reminder_days' => [7, 3, 1, 0],

    'plans' => [
        'trial' => [
            'name' => 'Trial',
            'employee_limit' => 25,
            'price_label' => 'Free for 14 days',
            'monthly_price' => null,
            'stripe_price_id' => null,
            'paypal_plan_id' => null,
            'enabled_features' => [
                PlanFeature::Employees->value,
                PlanFeature::Leave->value,
                PlanFeature::Documents->value,
                PlanFeature::TeamInvitations->value,
                PlanFeature::Departments->value,
                PlanFeature::Chat->value,
            ],
        ],
        'starter' => [
            'name' => 'Start',
            'employee_limit' => 50,
            'price_label' => '€20 / month',
            'monthly_price' => 20,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER') ?: null,
            'paypal_plan_id' => null,
            'enabled_features' => [
                PlanFeature::Employees->value,
                PlanFeature::Leave->value,
                PlanFeature::Documents->value,
                PlanFeature::TeamInvitations->value,
                PlanFeature::Departments->value,
                PlanFeature::EmployeeImport->value,
                PlanFeature::Projects->value,
                PlanFeature::TimeTracking->value,
                PlanFeature::Expenses->value,
                PlanFeature::Chat->value,
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'employee_limit' => 200,
            'price_label' => '€49.90 / month',
            'monthly_price' => 49.9,
            'stripe_price_id' => env('STRIPE_PRICE_PRO') ?: null,
            'paypal_plan_id' => null,
            'enabled_features' => PlanFeature::values(),
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'employee_limit' => null,
            'price_label' => 'Custom pricing',
            'monthly_price' => null,
            'stripe_price_id' => null,
            'paypal_plan_id' => null,
            'enabled_features' => PlanFeature::values(),
        ],
    ],

];
