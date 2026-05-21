<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionPlan;
use App\Support\StripeConfig;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Stripe;

class StripeCheckCommand extends Command
{
    protected $signature = 'billing:stripe-check {--live : Validate price IDs against the Stripe API}';

    protected $description = 'Verify Stripe billing configuration for checkout and webhooks';

    public function handle(): int
    {
        $diagnostics = StripeConfig::fullDiagnostics();

        $this->components->info('Stripe configuration check');

        $this->line('APP_URL: '.$diagnostics['app_url']);

        if (str_contains($diagnostics['app_url'], 'localhost') && ! app()->environment('local')) {
            $this->components->warn('APP_URL points to localhost — Stripe redirect URLs may fail in production.');
        }

        if ($diagnostics['checkout_ready']) {
            $this->components->info('Checkout: ready');
        } else {
            $this->components->error('Checkout: not ready');
            foreach ($diagnostics['missing_checkout'] as $key) {
                $this->line("  - missing {$key}");
            }
        }

        if ($diagnostics['webhook_ready']) {
            $this->components->info('Webhooks: ready');
        } else {
            $this->components->warn('Webhooks: not configured');
            foreach ($diagnostics['missing_webhook'] as $key) {
                $this->line("  - missing {$key}");
            }
        }

        foreach ([SubscriptionPlan::Starter, SubscriptionPlan::Pro] as $plan) {
            $priceId = StripeConfig::priceIdFor($plan);
            $label = $plan->label();
            $this->line(sprintf('%s price ID: %s', $label, $priceId ?? '(not set)'));
        }

        if (! $this->option('live')) {
            if (! StripeConfig::isConfigured()) {
                $this->newLine();
                $this->line('Add STRIPE_KEY and STRIPE_SECRET to .env, then configure plan price IDs in Admin → Plans & billing.');
                $this->line('Run with --live to verify price IDs against Stripe.');

                return self::FAILURE;
            }

            if (! $diagnostics['checkout_ready']) {
                return self::FAILURE;
            }

            $this->newLine();
            $this->line('Config looks complete. Run with --live to verify Stripe API access.');

            return $diagnostics['webhook_ready'] ? self::SUCCESS : self::SUCCESS;
        }

        if (! StripeConfig::isConfigured()) {
            $this->components->error('Cannot run live check — STRIPE_KEY and STRIPE_SECRET are required.');

            return self::FAILURE;
        }

        Stripe::setApiKey(config('stripe.secret'));

        try {
            \Stripe\Balance::retrieve();
            $this->components->info('Stripe API: connected');
        } catch (ApiErrorException $exception) {
            $this->components->error('Stripe API: '.$exception->getMessage());

            return self::FAILURE;
        }

        $liveOk = true;

        foreach ([SubscriptionPlan::Starter, SubscriptionPlan::Pro] as $plan) {
            $priceId = StripeConfig::priceIdFor($plan);

            if ($priceId === null) {
                continue;
            }

            try {
                $price = Price::retrieve($priceId);
                $amount = $price->unit_amount ?? 0;
                $currency = strtoupper((string) ($price->currency ?? config('stripe.currency', 'eur')));
                $this->components->info(sprintf(
                    '%s price valid: %s (%s %.2f / %s)',
                    $plan->label(),
                    $priceId,
                    $currency,
                    $amount / 100,
                    $price->recurring->interval ?? 'once',
                ));
            } catch (ApiErrorException $exception) {
                $liveOk = false;
                $this->components->error(sprintf('%s price invalid (%s): %s', $plan->label(), $priceId, $exception->getMessage()));
            }
        }

        return ($diagnostics['checkout_ready'] && $liveOk) ? self::SUCCESS : self::FAILURE;
    }
}
