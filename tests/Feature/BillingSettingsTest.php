<?php

namespace Tests\Feature;

use App\Enums\PlanFeature;
use App\Models\User;
use App\Services\BillingConfigurationService;
use App\Services\OrganizationBillingService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BillingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.billing');
    }

    public function test_super_admin_can_configure_plans(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $billing = app(BillingConfigurationService::class);

        $this->actingAs($admin)
            ->get(route('admin.billing.edit'))
            ->assertOk()
            ->assertSee('Starter', false)
            ->assertSee('Stripe price ID', false)
            ->assertSee('Included features', false);

        $payload = [
            'trial_days' => 21,
            'plans' => $this->planPayload($billing),
        ];

        $payload['plans']['starter']['monthly_price'] = 39;
        $payload['plans']['starter']['employee_limit'] = 25;
        $payload['plans']['starter']['price_label'] = '€39 / month';

        $this->actingAs($admin)
            ->put(route('admin.billing.update'), $payload)
            ->assertRedirect(route('admin.billing.edit'))
            ->assertSessionHas('status');

        $this->assertSame(21, $billing->trialDays());
        $this->assertSame(39, $billing->plan('starter')['monthly_price']);
        $this->assertSame(25, $billing->plan('starter')['employee_limit']);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'platform.billing_updated',
        ]);
    }

    public function test_super_admin_can_configure_plan_features(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $billing = app(BillingConfigurationService::class);

        $payload = [
            'trial_days' => 14,
            'plans' => $this->planPayload($billing),
        ];

        $payload['plans']['starter']['enabled_features'] = [
            PlanFeature::Employees->value,
            PlanFeature::Leave->value,
            PlanFeature::Documents->value,
        ];
        $payload['plans']['pro']['enabled_features'] = PlanFeature::values();

        $this->actingAs($admin)
            ->put(route('admin.billing.update'), $payload)
            ->assertRedirect(route('admin.billing.edit'));

        $this->assertSame(
            ['employees', 'leave', 'documents'],
            $billing->enabledFeatures('starter'),
        );
        $this->assertTrue($billing->plan('pro')['payroll']);
        $this->assertContains('Payroll', $billing->plan('pro')['features']);
    }

    public function test_super_admin_can_configure_payment_provider_plan_ids(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $billing = app(BillingConfigurationService::class);

        $payload = [
            'trial_days' => 14,
            'plans' => $this->planPayload($billing),
        ];

        $payload['plans']['starter']['stripe_price_id'] = 'price_admin_starter';
        $payload['plans']['starter']['paypal_plan_id'] = 'P-ADMIN-STARTER';
        $payload['plans']['pro']['stripe_price_id'] = 'price_admin_pro';
        $payload['plans']['pro']['paypal_plan_id'] = 'P-ADMIN-PRO';

        $this->actingAs($admin)
            ->put(route('admin.billing.update'), $payload)
            ->assertRedirect(route('admin.billing.edit'));

        $this->assertSame('price_admin_starter', $billing->plan('starter')['stripe_price_id']);
        $this->assertSame('P-ADMIN-STARTER', $billing->plan('starter')['paypal_plan_id']);
        $this->assertSame('price_admin_pro', $billing->plan('pro')['stripe_price_id']);
        $this->assertSame('P-ADMIN-PRO', $billing->plan('pro')['paypal_plan_id']);

        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
        ]);

        $this->assertTrue(\App\Support\StripeConfig::isCheckoutReadyFor(\App\Enums\SubscriptionPlan::Starter));
        $this->assertTrue(\App\Support\PayPalConfig::isCheckoutReadyFor(\App\Enums\SubscriptionPlan::Pro));
    }

    public function test_plan_configuration_affects_organization_limits_and_features(): void
    {
        app(BillingConfigurationService::class)->update(14, [
            'trial' => [
                'name' => 'Trial',
                'employee_limit' => 10,
                'price_label' => 'Free',
                'monthly_price' => null,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => [PlanFeature::Employees->value, PlanFeature::Leave->value],
            ],
            'starter' => [
                'name' => 'Starter',
                'employee_limit' => 30,
                'price_label' => '€30 / month',
                'monthly_price' => 30,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => [PlanFeature::Employees->value, PlanFeature::Leave->value, PlanFeature::Documents->value],
            ],
            'pro' => [
                'name' => 'Pro',
                'employee_limit' => 100,
                'price_label' => '€80 / month',
                'monthly_price' => 80,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => PlanFeature::values(),
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'employee_limit' => null,
                'price_label' => 'Custom',
                'monthly_price' => null,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => PlanFeature::values(),
            ],
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $billing = app(OrganizationBillingService::class);

        $this->assertSame(10, $billing->employeeLimit($result['organization']));
        $this->assertTrue($billing->hasFeature($result['organization'], PlanFeature::Leave));
        $this->assertFalse($billing->hasFeature($result['organization'], PlanFeature::Payroll));
    }

    public function test_landing_page_reflects_configured_pricing(): void
    {
        app(BillingConfigurationService::class)->update(14, [
            'trial' => [
                'name' => 'Trial',
                'employee_limit' => 25,
                'price_label' => 'Free for 14 days',
                'monthly_price' => null,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => [PlanFeature::Employees->value, PlanFeature::Leave->value],
            ],
            'starter' => [
                'name' => 'Starter',
                'employee_limit' => 25,
                'price_label' => '€39 / month',
                'monthly_price' => 39,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => [PlanFeature::Employees->value, PlanFeature::Leave->value],
            ],
            'pro' => [
                'name' => 'Pro',
                'employee_limit' => 200,
                'price_label' => '€99 / month',
                'monthly_price' => 99,
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
                'enabled_features' => [PlanFeature::Employees->value, PlanFeature::Leave->value, PlanFeature::Payroll->value],
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
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('€39', false)
            ->assertSee('Up to 25 employees', false)
            ->assertSee('Employee directory', false);
    }

    public function test_non_super_admin_cannot_access_billing_settings(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->get(route('admin.billing.edit'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function planPayload(BillingConfigurationService $billing): array
    {
        $payload = [];

        foreach ($billing->configurablePlanKeys() as $key) {
            $plan = $billing->plan($key);
            $payload[$key] = [
                'name' => $plan['name'],
                'employee_limit' => $plan['employee_limit'],
                'price_label' => $plan['price_label'],
                'monthly_price' => $plan['monthly_price'],
                'stripe_price_id' => $plan['stripe_price_id'],
                'paypal_plan_id' => $plan['paypal_plan_id'] ?? null,
                'enabled_features' => $plan['enabled_features'] ?? [],
            ];
        }

        return $payload;
    }
}
