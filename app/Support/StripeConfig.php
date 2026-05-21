<?php

namespace App\Support;

use App\Enums\SubscriptionPlan;
use App\Services\BillingConfigurationService;

class StripeConfig
{
    public static function isConfigured(): bool
    {
        return filled(config('stripe.secret')) && filled(config('stripe.key'));
    }

    public static function priceIdFor(SubscriptionPlan $plan): ?string
    {
        $priceId = app(BillingConfigurationService::class)->plan($plan->value)['stripe_price_id'] ?? null;

        return is_string($priceId) && $priceId !== '' ? $priceId : null;
    }

    public static function isCheckoutReadyFor(SubscriptionPlan $plan): bool
    {
        return self::isConfigured() && self::priceIdFor($plan) !== null;
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

        if (! filled(config('stripe.key'))) {
            $missingEnv[] = 'STRIPE_KEY';
        }

        if (! filled(config('stripe.secret'))) {
            $missingEnv[] = 'STRIPE_SECRET';
        }

        if (self::priceIdFor(SubscriptionPlan::Starter) === null) {
            $missingAdmin[] = 'Starter → Stripe price ID';
        }

        if (self::priceIdFor(SubscriptionPlan::Pro) === null) {
            $missingAdmin[] = 'Pro → Stripe price ID';
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
     *     app_url: string
     * }
     */
    public static function fullDiagnostics(): array
    {
        $checkout = self::checkoutDiagnostics();

        $missingWebhook = filled(config('stripe.webhook_secret'))
            ? []
            : ['STRIPE_WEBHOOK_SECRET'];

        return [
            'checkout_ready' => $checkout['ready'],
            'webhook_ready' => $missingWebhook === [],
            'missing_checkout' => $checkout['missing'],
            'missing_checkout_env' => $checkout['missing_env'],
            'missing_checkout_admin' => $checkout['missing_admin'],
            'missing_webhook' => $missingWebhook,
            'app_url' => (string) config('app.url'),
        ];
    }

    public static function planForPriceId(string $priceId): ?SubscriptionPlan
    {
        foreach (SubscriptionPlan::selectable() as $plan) {
            if (self::priceIdFor($plan) === $priceId) {
                return $plan;
            }
        }

        return null;
    }
}
