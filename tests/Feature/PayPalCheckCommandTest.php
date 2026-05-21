<?php

namespace Tests\Feature;

use App\Support\PayPalConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PayPalCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.billing');
    }

    public function test_command_reports_missing_paypal_configuration(): void
    {
        config([
            'paypal.client_id' => null,
            'paypal.client_secret' => null,
            'billing.plans.starter.paypal_plan_id' => null,
            'billing.plans.pro.paypal_plan_id' => null,
        ]);

        $this->artisan('billing:paypal-check')
            ->assertFailed()
            ->expectsOutputToContain('Checkout: not ready');
    }

    public function test_command_reports_ready_checkout_configuration(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'paypal.webhook_id' => 'WH-TEST',
            'billing.plans.starter.paypal_plan_id' => 'P-STARTER-TEST',
            'billing.plans.pro.paypal_plan_id' => 'P-PRO-TEST',
        ]);

        $this->artisan('billing:paypal-check')
            ->assertSuccessful()
            ->expectsOutputToContain('Checkout: ready')
            ->expectsOutputToContain('Webhooks: ready');
    }

    public function test_full_diagnostics_include_webhook_status(): void
    {
        config(['paypal.webhook_id' => null]);

        $diagnostics = PayPalConfig::fullDiagnostics();

        $this->assertFalse($diagnostics['webhook_ready']);
        $this->assertContains('PAYPAL_WEBHOOK_ID', $diagnostics['missing_webhook']);
    }
}
