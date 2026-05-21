<?php

namespace App\Support;

use App\Enums\SubscriptionPlan;
use App\Services\BillingConfigurationService;

class PayPalConfig
{
    public static function isConfigured(): bool
    {
        return filled(config('paypal.client_id')) && filled(config('paypal.client_secret'));
    }

    public static function apiBaseUrl(): string
    {
        return config('paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public static function planIdFor(SubscriptionPlan $plan): ?string
    {
        $planId = app(BillingConfigurationService::class)->plan($plan->value)['paypal_plan_id'] ?? null;

        return is_string($planId) && $planId !== '' ? $planId : null;
    }

    public static function isCheckoutReadyFor(SubscriptionPlan $plan): bool
    {
        return self::isConfigured() && self::planIdFor($plan) !== null;
    }

    /**
     * @return array{
     *     ready: bool,
     *     missing: list<string>,
     *     missing_env: list<string>,
     *     missing_admin: list<string>
     * }
     */
    public static function checkoutDiagnostics(): array
    {
        $missingEnv = [];
        $missingAdmin = [];

        if (! filled(config('paypal.client_id'))) {
            $missingEnv[] = 'PAYPAL_CLIENT_ID';
        }

        if (! filled(config('paypal.client_secret'))) {
            $missingEnv[] = 'PAYPAL_CLIENT_SECRET';
        }

        if (self::planIdFor(SubscriptionPlan::Starter) === null) {
            $missingAdmin[] = 'Starter → PayPal plan ID';
        }

        if (self::planIdFor(SubscriptionPlan::Pro) === null) {
            $missingAdmin[] = 'Pro → PayPal plan ID';
        }

        return [
            'ready' => $missingEnv === [] && $missingAdmin === [],
            'missing_env' => $missingEnv,
            'missing_admin' => $missingAdmin,
            'missing' => array_merge($missingEnv, $missingAdmin),
        ];
    }

    /**
     * @return array{
     *     checkout_ready: bool,
     *     webhook_ready: bool,
     *     missing_checkout: list<string>,
     *     missing_checkout_env: list<string>,
     *     missing_checkout_admin: list<string>,
     *     missing_webhook: list<string>,
     *     mode: string
     * }
     */
    public static function fullDiagnostics(): array
    {
        $checkout = self::checkoutDiagnostics();

        $missingWebhook = filled(config('paypal.webhook_id'))
            ? []
            : ['PAYPAL_WEBHOOK_ID'];

        return [
            'checkout_ready' => $checkout['ready'],
            'webhook_ready' => $missingWebhook === [],
            'missing_checkout' => $checkout['missing'],
            'missing_checkout_env' => $checkout['missing_env'],
            'missing_checkout_admin' => $checkout['missing_admin'],
            'missing_webhook' => $missingWebhook,
            'mode' => (string) config('paypal.mode', 'sandbox'),
        ];
    }

    public static function planForPayPalPlanId(string $planId): ?SubscriptionPlan
    {
        foreach (SubscriptionPlan::selectable() as $plan) {
            if (self::planIdFor($plan) === $planId) {
                return $plan;
            }
        }

        return null;
    }
}
