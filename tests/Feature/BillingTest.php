<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_starts_trial(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization']->fresh();

        $this->assertSame(SubscriptionPlan::Trial, $organization->plan);
        $this->assertNotNull($organization->trial_ends_at);
        $this->assertTrue($organization->trial_ends_at->isFuture());
    }

    public function test_owner_can_view_billing_page(): void
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
            ->assertSee('Current plan', false)
            ->assertSee('Trial', false)
            ->assertSee(__('billing.upgrade'), false);
    }

    public function test_trial_without_end_date_still_shows_upgrade_banner(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $result['organization']->update(['trial_ends_at' => null]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee(__('billing.upgrade'), false);
    }

    public function test_hr_user_sees_upgrade_on_trial(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $hr = \App\Models\User::factory()->create();
        $result['organization']->users()->attach($hr->id, [
            'role' => \App\Enums\OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee(__('billing.upgrade'), false);
    }

    public function test_trial_workspace_shows_upgrade_banner(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee(__('billing.upgrade'), false)
            ->assertSee(__('billing.trial_banner', ['days' => app(\App\Services\OrganizationBillingService::class)->trialDaysRemaining($result['organization'])]), false);
    }

    public function test_employee_limit_blocks_new_hires(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Starter->value]);

        Employee::factory()
            ->count(50)
            ->forOrganization($organization)
            ->create();

        $this->actingAs($result['user'])
            ->post($this->workspaceRoute('employees.store', $organization), [
                'first_name' => 'Extra',
                'last_name' => 'Hire',
                'employment_status' => EmploymentStatus::Active->value,
            ])
            ->assertSessionHasErrors('first_name');
    }

    public function test_expired_trial_redirects_to_billing(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($result['user'])
            ->get($this->workspaceRoute('employees.index', $organization))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization));
    }

    public function test_active_stripe_subscription_restores_access_after_trial(): void
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
            'stripe_subscription_status' => 'active',
        ]);

        $billing = app(\App\Services\OrganizationBillingService::class);

        $this->assertTrue($billing->hasActiveAccess($organization));

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('employees.index', $organization))
            ->assertOk();
    }

    public function test_checkout_with_stripe_keys_but_no_prices_returns_error(): void
    {
        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
            'billing.plans.starter.stripe_price_id' => null,
            'billing.plans.pro.stripe_price_id' => null,
            'billing.allow_manual_upgrade' => false,
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
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_checkout_without_stripe_returns_error_when_manual_disabled(): void
    {
        config([
            'stripe.secret' => null,
            'stripe.key' => null,
            'billing.allow_manual_upgrade' => false,
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
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_manual_upgrade_activates_plan_in_local(): void
    {
        config([
            'stripe.secret' => null,
            'stripe.key' => null,
            'billing.allow_manual_upgrade' => true,
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
                'plan' => SubscriptionPlan::Pro->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $result['organization']->refresh();

        $this->assertSame(SubscriptionPlan::Pro, $result['organization']->plan);
        $this->assertNull($result['organization']->trial_ends_at);
    }
}
