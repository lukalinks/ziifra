<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Services\BillingConfigurationService;
use App\Services\StripePriceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripePriceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ensure_price_for_plan_skips_when_stripe_not_configured(): void
    {
        config([
            'stripe.key' => null,
            'stripe.secret' => null,
        ]);

        $service = app(StripePriceSyncService::class);

        $this->assertNull($service->ensurePriceForPlan(SubscriptionPlan::Starter));
    }

    public function test_billing_update_triggers_stripe_sync_when_configured(): void
    {
        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
        ]);

        $sync = Mockery::mock(StripePriceSyncService::class);
        $sync->shouldReceive('syncAll')->once();
        $this->app->instance(StripePriceSyncService::class, $sync);

        $billing = app(BillingConfigurationService::class);

        $billing->update(14, [
            'trial' => ['name' => 'Trial', 'price_label' => 'Free', 'monthly_price' => null, 'enabled_features' => ['employees']],
            'starter' => [
                'name' => 'Starter',
                'employee_limit' => 50,
                'price_label' => '€20 / month',
                'monthly_price' => 20,
                'enabled_features' => ['employees', 'leave'],
            ],
            'pro' => [
                'name' => 'Pro',
                'employee_limit' => 200,
                'price_label' => '€49.90 / month',
                'monthly_price' => 49.9,
                'enabled_features' => ['employees', 'payroll'],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price_label' => 'Custom',
                'monthly_price' => null,
                'enabled_features' => [],
            ],
        ]);
    }
}
