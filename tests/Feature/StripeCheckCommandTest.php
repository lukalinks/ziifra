<?php

namespace Tests\Feature;

use App\Support\StripeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StripeCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.billing');
    }

    public function test_command_reports_missing_stripe_configuration(): void
    {
        config([
            'stripe.key' => null,
            'stripe.secret' => null,
            'billing.plans.starter.stripe_price_id' => null,
            'billing.plans.pro.stripe_price_id' => null,
        ]);

        $this->artisan('billing:stripe-check')
            ->assertFailed()
            ->expectsOutputToContain('Checkout: not ready');
    }

    public function test_command_reports_ready_checkout_configuration(): void
    {
        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
            'stripe.webhook_secret' => 'whsec_test',
            'billing.plans.starter.stripe_price_id' => 'price_starter_test',
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        $this->artisan('billing:stripe-check')
            ->assertSuccessful()
            ->expectsOutputToContain('Checkout: ready')
            ->expectsOutputToContain('Webhooks: ready');
    }

    public function test_full_diagnostics_include_webhook_status(): void
    {
        config(['stripe.webhook_secret' => null]);

        $diagnostics = StripeConfig::fullDiagnostics();

        $this->assertFalse($diagnostics['webhook_ready']);
        $this->assertContains('STRIPE_WEBHOOK_SECRET', $diagnostics['missing_webhook']);
    }
}
