<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_webhook_updates_organization(): void
    {
        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
            'stripe.webhook_secret' => null,
            'billing.plans.starter.stripe_price_id' => 'price_starter_test',
        ]);

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
                    'id' => 'sub_test_456',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'current_period_end' => now()->addMonth()->timestamp,
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                        'plan' => 'starter',
                    ],
                    'items' => [
                        'data' => [
                            ['price' => ['id' => 'price_starter_test']],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postJson(route('stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $organization->refresh();

        $this->assertSame('sub_test_456', $organization->stripe_subscription_id);
        $this->assertSame('active', $organization->stripe_subscription_status);
        $this->assertSame(SubscriptionPlan::Starter, $organization->plan);
    }
}
