<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Support\StripeConfig;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripePriceSyncService
{
    public function __construct(
        protected BillingConfigurationService $billing,
    ) {
        if (StripeConfig::isConfigured()) {
            Stripe::setApiKey(config('stripe.secret'));
        }
    }

    /**
     * Sync Starter and Pro Stripe prices from platform plan amounts.
     */
    public function syncAll(): void
    {
        if (! StripeConfig::isConfigured()) {
            return;
        }

        foreach ([SubscriptionPlan::Starter, SubscriptionPlan::Pro] as $plan) {
            $this->ensurePriceForPlan($plan);
        }
    }

    /**
     * Ensure a Stripe Price exists for checkout; creates or updates mapping when needed.
     */
    public function ensurePriceForPlan(SubscriptionPlan $plan): ?string
    {
        if (! StripeConfig::isConfigured()) {
            return null;
        }

        if (! in_array($plan, [SubscriptionPlan::Starter, SubscriptionPlan::Pro], true)) {
            return null;
        }

        $config = $this->billing->plan($plan->value);
        $monthlyPrice = $config['monthly_price'] ?? null;

        if ($monthlyPrice === null || (float) $monthlyPrice <= 0) {
            return null;
        }

        $existingId = $config['stripe_price_id'] ?? null;

        if (is_string($existingId) && $existingId !== '' && $this->priceMatches($existingId, (float) $monthlyPrice)) {
            return $existingId;
        }

        try {
            $productId = $this->ensureProduct($plan, (string) ($config['name'] ?? $plan->label()));
            $priceId = $this->createPrice($plan, $productId, (float) $monthlyPrice);
            $this->billing->updateStripePriceId($plan->value, $priceId, $productId);

            return $priceId;
        } catch (ApiErrorException $exception) {
            report($exception);

            return is_string($existingId) && $existingId !== '' ? $existingId : null;
        }
    }

    protected function ensureProduct(SubscriptionPlan $plan, string $name): string
    {
        $storedProductId = $this->billing->stripeProductId($plan->value);

        if (is_string($storedProductId) && $storedProductId !== '') {
            try {
                Product::retrieve($storedProductId);

                return $storedProductId;
            } catch (ApiErrorException) {
                // Create a new product if the stored one was removed in Stripe.
            }
        }

        $product = Product::create([
            'name' => 'ZIIFRA '.$name,
            'metadata' => [
                'ziifra_plan_key' => $plan->value,
                'app' => config('app.name', 'ZIIFRA'),
            ],
        ]);

        return $product->id;
    }

    protected function createPrice(SubscriptionPlan $plan, string $productId, float $monthlyPrice): string
    {
        $currency = strtolower((string) config('stripe.currency', 'eur'));
        $amountCents = (int) round($monthlyPrice * 100);

        $price = Price::create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $amountCents,
            'recurring' => ['interval' => 'month'],
            'metadata' => [
                'ziifra_plan_key' => $plan->value,
            ],
        ]);

        return $price->id;
    }

    protected function priceMatches(string $priceId, float $monthlyPrice): bool
    {
        try {
            $price = Price::retrieve($priceId);
        } catch (ApiErrorException) {
            return false;
        }

        $currency = strtolower((string) config('stripe.currency', 'eur'));
        $expectedCents = (int) round($monthlyPrice * 100);

        return (int) ($price->unit_amount ?? 0) === $expectedCents
            && strtolower((string) ($price->currency ?? '')) === $currency
            && ($price->recurring->interval ?? null) === 'month'
            && ($price->active ?? true);
    }
}
