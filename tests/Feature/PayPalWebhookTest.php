<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_webhook_updates_organization(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'paypal.webhook_id' => null,
            'billing.plans.starter.paypal_plan_id' => 'P-STARTER-TEST',
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];

        $payload = json_encode([
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource' => [
                'id' => 'I-SUB-WEBHOOK',
                'plan_id' => 'P-STARTER-TEST',
                'status' => 'ACTIVE',
                'custom_id' => 'org:'.$organization->id.':plan:starter',
                'billing_info' => [
                    'next_billing_time' => now()->addMonth()->toIso8601String(),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('paypal.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload,
        )->assertOk();

        $organization->refresh();

        $this->assertSame('I-SUB-WEBHOOK', $organization->paypal_subscription_id);
        $this->assertSame('ACTIVE', $organization->paypal_subscription_status);
        $this->assertSame(SubscriptionPlan::Starter, $organization->plan);
    }

    public function test_cancelled_subscription_webhook_downgrades_organization(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'paypal.webhook_id' => null,
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update([
            'plan' => SubscriptionPlan::Starter->value,
            'paypal_subscription_id' => 'I-SUB-CANCEL',
            'paypal_subscription_status' => 'ACTIVE',
            'billing_payment_provider' => 'paypal',
        ]);

        $payload = json_encode([
            'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
            'resource' => [
                'id' => 'I-SUB-CANCEL',
                'status' => 'CANCELLED',
                'custom_id' => 'org:'.$organization->id.':plan:starter',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('paypal.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload,
        )->assertOk();

        $organization->refresh();

        $this->assertNull($organization->paypal_subscription_id);
        $this->assertSame('CANCELLED', $organization->paypal_subscription_status);
        $this->assertSame(SubscriptionPlan::Trial, $organization->plan);
    }
}
