<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Services\RegisterOrganizationService;
use App\Services\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
            'stripe.webhook_secret' => 'whsec_test_secret',
            'billing.plans.starter.stripe_price_id' => 'price_starter_test',
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);
    }

    public function test_signed_webhook_updates_pro_plan(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['stripe_id' => 'cus_test_123']);

        $payload = json_encode([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_pro',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'current_period_end' => now()->addMonth()->timestamp,
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                        'plan' => 'pro',
                    ],
                    'items' => [
                        'data' => [
                            ['price' => ['id' => 'price_pro_test']],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, config('stripe.webhook_secret'));

        $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Stripe-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload,
        )->assertOk();

        $organization->refresh();

        $this->assertSame(SubscriptionPlan::Pro, $organization->plan);
        $this->assertSame('sub_test_pro', $organization->stripe_subscription_id);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $payload = json_encode(['type' => 'customer.subscription.updated', 'data' => ['object' => []]]);

        $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            ['HTTP_Stripe-Signature' => 'invalid'],
            $payload,
        )->assertForbidden();
    }

    public function test_subscription_deleted_reverts_organization_to_trial(): void
    {
        config(['stripe.webhook_secret' => null]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update([
            'stripe_id' => 'cus_test_123',
            'plan' => SubscriptionPlan::Starter->value,
            'stripe_subscription_id' => 'sub_old',
            'stripe_subscription_status' => 'active',
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_old',
                    'customer' => 'cus_test_123',
                    'status' => 'canceled',
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                    ],
                    'items' => ['data' => []],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postJson(route('stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $organization->refresh();

        $this->assertSame(SubscriptionPlan::Trial, $organization->plan);
        $this->assertNull($organization->stripe_subscription_id);
        $this->assertSame('canceled', $organization->stripe_subscription_status);
    }

    public function test_checkout_success_syncs_stripe_session(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organizationId = $organization->id;

        $this->mock(StripeBillingService::class, function ($mock) use ($organizationId): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('syncCheckoutSession')
                ->once()
                ->with('cs_test_123', Mockery::on(
                    fn ($organization) => $organization instanceof \App\Models\Organization
                        && $organization->id === $organizationId,
                ));
        });

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('settings.billing.checkout.success', $organization, [
                'session_id' => 'cs_test_123',
            ]))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization))
            ->assertSessionHas('status');
    }

    public function test_billing_page_shows_stripe_checkout_when_configured(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->get($this->workspaceRoute('settings.billing', $result['organization']))
            ->assertOk()
            ->assertSee(__('billing.stripe_pay_with'), false)
            ->assertDontSee(__('billing.stripe_setup_incomplete'), false);
    }

    public function test_billing_page_shows_stripe_setup_status_for_super_admin(): void
    {
        $admin = \App\Models\User::factory()->superAdmin()->create();
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $result['organization']->users()->attach($admin->id, [
            'role' => \App\Enums\OrganizationRole::Owner->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('settings.billing', $result['organization']))
            ->assertOk()
            ->assertSee(__('billing.stripe_setup_ready'), false);
    }

    public function test_portal_redirects_when_customer_exists(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['stripe_id' => 'cus_test_123']);

        $this->mock(StripeBillingService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('portalUrl')
                ->once()
                ->andReturn('https://billing.stripe.com/session/test');
        });

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('settings.billing.portal', $organization))
            ->assertRedirect('https://billing.stripe.com/session/test');
    }

    public function test_webhook_returns_service_unavailable_when_stripe_not_configured(): void
    {
        config([
            'stripe.secret' => null,
            'stripe.key' => null,
        ]);

        $this->postJson(route('stripe.webhook'), ['type' => 'ping'])
            ->assertStatus(503);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $signedPayload, $secret);
    }
}
