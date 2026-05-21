<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_redirects_to_paypal_approval_url(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'paypal.mode' => 'sandbox',
            'billing.plans.starter.paypal_plan_id' => 'P-STARTER-TEST',
            'stripe.secret' => null,
            'stripe.key' => null,
            'billing.allow_manual_upgrade' => false,
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'token_test',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://api-m.sandbox.paypal.com/v1/billing/subscriptions' => Http::response([
                'id' => 'I-SUB-TEST',
                'status' => 'APPROVAL_PENDING',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-TEST'],
                ],
            ]),
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.billing.checkout', $result['organization']), [
                'plan' => SubscriptionPlan::Starter->value,
                'provider' => 'paypal',
            ])
            ->assertRedirect('https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-TEST');
    }

    public function test_paypal_success_syncs_subscription(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'paypal.mode' => 'sandbox',
            'billing.plans.starter.paypal_plan_id' => 'P-STARTER-TEST',
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'token_test',
            ]),
            'https://api-m.sandbox.paypal.com/v1/billing/subscriptions/I-SUB-TEST' => Http::response([
                'id' => 'I-SUB-TEST',
                'plan_id' => 'P-STARTER-TEST',
                'status' => 'ACTIVE',
                'custom_id' => 'org:'.$organization->id.':plan:starter',
                'billing_info' => [
                    'next_billing_time' => now()->addMonth()->toIso8601String(),
                ],
            ]),
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('settings.billing.paypal.success', $organization, [
                'subscription_id' => 'I-SUB-TEST',
            ]))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization))
            ->assertSessionHas('status');

        $organization->refresh();

        $this->assertSame('I-SUB-TEST', $organization->paypal_subscription_id);
        $this->assertSame('ACTIVE', $organization->paypal_subscription_status);
        $this->assertSame(SubscriptionPlan::Starter, $organization->plan);
        $this->assertSame('paypal', $organization->billing_payment_provider);
    }

    public function test_active_paypal_subscription_restores_access_after_trial(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update([
            'trial_ends_at' => now()->subDay(),
            'plan' => SubscriptionPlan::Starter->value,
            'paypal_subscription_status' => 'ACTIVE',
        ]);

        $billing = app(\App\Services\OrganizationBillingService::class);

        $this->assertTrue($billing->hasActiveAccess($organization));

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('employees.index', $organization))
            ->assertOk();
    }

    public function test_checkout_with_paypal_keys_but_no_plans_returns_error(): void
    {
        config([
            'paypal.client_id' => 'client_test',
            'paypal.client_secret' => 'secret_test',
            'billing.plans.starter.paypal_plan_id' => null,
            'billing.plans.pro.paypal_plan_id' => null,
            'billing.allow_manual_upgrade' => false,
            'stripe.secret' => null,
            'stripe.key' => null,
        ]);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.billing.checkout', $result['organization']), [
                'plan' => SubscriptionPlan::Starter->value,
                'provider' => 'paypal',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
