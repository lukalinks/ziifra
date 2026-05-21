<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionPlan;
use App\Support\PayPalConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PayPalCheckCommand extends Command
{
    protected $signature = 'billing:paypal-check {--live : Validate plan IDs against the PayPal API}';

    protected $description = 'Verify PayPal billing configuration for checkout and webhooks';

    public function handle(): int
    {
        $diagnostics = PayPalConfig::fullDiagnostics();

        $this->components->info('PayPal configuration check');
        $this->line('Mode: '.$diagnostics['mode']);

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
            $planId = PayPalConfig::planIdFor($plan);
            $this->line(sprintf('%s plan ID: %s', $plan->label(), $planId ?? '(not set)'));
        }

        if (! $this->option('live')) {
            if (! PayPalConfig::isConfigured()) {
                $this->newLine();
                $this->line('Add PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET to .env, then configure plan IDs in Admin → Plans & billing.');
                $this->line('Run with --live to verify plan IDs against PayPal.');

                return self::FAILURE;
            }

            if (! $diagnostics['checkout_ready']) {
                return self::FAILURE;
            }

            $this->newLine();
            $this->line('Config looks complete. Run with --live to verify PayPal API access.');

            return self::SUCCESS;
        }

        if (! PayPalConfig::isConfigured()) {
            $this->components->error('Cannot run live check — PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET are required.');

            return self::FAILURE;
        }

        try {
            $token = $this->accessToken();
            $this->components->info('PayPal API: connected');
        } catch (\Throwable $exception) {
            $this->components->error('PayPal API: '.$exception->getMessage());

            return self::FAILURE;
        }

        $liveOk = true;

        foreach ([SubscriptionPlan::Starter, SubscriptionPlan::Pro] as $plan) {
            $planId = PayPalConfig::planIdFor($plan);

            if ($planId === null) {
                continue;
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->get(PayPalConfig::apiBaseUrl().'/v1/billing/plans/'.$planId);

            if ($response->successful()) {
                $name = (string) ($response->json('name') ?? $planId);
                $status = (string) ($response->json('status') ?? 'unknown');
                $this->components->info(sprintf('%s plan valid: %s (%s)', $plan->label(), $planId, $status.' — '.$name));
            } else {
                $liveOk = false;
                $this->components->error(sprintf('%s plan invalid (%s): %s', $plan->label(), $planId, $response->body()));
            }
        }

        return ($diagnostics['checkout_ready'] && $liveOk) ? self::SUCCESS : self::FAILURE;
    }

    protected function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(
                (string) config('paypal.client_id'),
                (string) config('paypal.client_secret'),
            )
            ->acceptJson()
            ->post(PayPalConfig::apiBaseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($response->body());
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('PayPal access token missing from response.');
        }

        return $token;
    }
}
