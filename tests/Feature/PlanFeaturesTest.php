<?php

namespace Tests\Feature;

use App\Enums\PlanFeature;
use App\Enums\SubscriptionPlan;
use App\Models\User;
use App\Services\BillingConfigurationService;
use App\Services\OrganizationBillingService;
use App\Services\RegisterOrganizationService;
use App\Support\WorkspaceNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.billing');
    }

    public function test_starter_plan_blocks_payroll_and_reports_but_allows_projects(): void
    {
        $result = $this->registerOwner();

        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);
        $billing = app(OrganizationBillingService::class);

        $this->assertFalse($billing->hasFeature($organization, PlanFeature::Payroll));
        $this->assertFalse($billing->hasFeature($organization, PlanFeature::Reports));
        $this->assertTrue($billing->hasFeature($organization, PlanFeature::Projects));

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($result['user'])
            ->withSession($session)
            ->get($this->workspaceRoute('payroll.index', $organization))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization).'#plans')
            ->assertSessionHas('error');

        $this->actingAs($result['user'])
            ->withSession($session)
            ->get($this->workspaceRoute('reports.index', $organization))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization).'#plans')
            ->assertSessionHas('error');

        $this->actingAs($result['user'])
            ->withSession($session)
            ->get($this->workspaceRoute('projects.index', $organization))
            ->assertOk();
    }

    public function test_pro_plan_allows_payroll_reports_and_invoices(): void
    {
        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);
        $billing = app(OrganizationBillingService::class);

        $this->assertTrue($billing->hasFeature($organization, PlanFeature::Payroll));
        $this->assertTrue($billing->hasFeature($organization, PlanFeature::Reports));
        $this->assertTrue($billing->hasFeature($organization, PlanFeature::Invoices));

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($result['user'])->withSession($session)
            ->get($this->workspaceRoute('payroll.index', $organization))
            ->assertOk();

        $this->actingAs($result['user'])->withSession($session)
            ->get($this->workspaceRoute('reports.index', $organization))
            ->assertOk();

        $this->actingAs($result['user'])->withSession($session)
            ->get($this->workspaceRoute('invoices.index', $organization))
            ->assertOk();
    }

    public function test_starter_allows_expenses_and_time_tracking(): void
    {
        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($result['user'])->withSession($session)
            ->get($this->workspaceRoute('expenses.index', $organization))
            ->assertOk();

        $this->actingAs($result['user'])->withSession($session)
            ->get($this->workspaceRoute('time.index', $organization))
            ->assertOk();
    }

    public function test_admin_can_remove_documents_from_starter_and_block_route(): void
    {
        $this->configureStarterFeatures([
            PlanFeature::Employees->value,
            PlanFeature::Leave->value,
        ]);

        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $this->assertFalse(app(OrganizationBillingService::class)->hasFeature($organization, PlanFeature::Documents));

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('documents.index', $organization))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization).'#plans')
            ->assertSessionHas('error', __('billing.feature_upgrade_required', [
                'feature' => PlanFeature::Documents->label(),
            ]));
    }

    public function test_admin_can_remove_chat_from_starter_and_hide_navigation(): void
    {
        $this->configureStarterFeatures([
            PlanFeature::Employees->value,
            PlanFeature::Leave->value,
            PlanFeature::Documents->value,
        ]);

        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $labels = collect(app(WorkspaceNavigation::class)->groups($organization, $result['user']))
            ->flatMap(fn (array $group) => array_column($group['items'], 'label'))
            ->all();

        $this->assertNotContains(__('navigation.chat'), $labels);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('chat.index', $organization))
            ->assertRedirect($this->workspaceRoute('settings.billing', $organization).'#plans');
    }

    public function test_navigation_hides_payroll_and_reports_on_starter(): void
    {
        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $labels = collect(app(WorkspaceNavigation::class)->groups($organization, $result['user']))
            ->flatMap(fn (array $group) => array_column($group['items'], 'label'))
            ->all();

        $this->assertNotContains(__('navigation.payroll'), $labels);
        $this->assertNotContains(__('navigation.reports'), $labels);
        $this->assertContains(__('navigation.projects'), $labels);
    }

    public function test_marketing_features_reflect_enabled_plan_features(): void
    {
        $billingConfig = app(BillingConfigurationService::class);

        $billingConfig->update(14, [
            'trial' => $this->planInput($billingConfig, 'trial'),
            'starter' => array_merge($this->planInput($billingConfig, 'starter'), [
                'enabled_features' => [
                    PlanFeature::Employees->value,
                    PlanFeature::Leave->value,
                    PlanFeature::Payroll->value,
                ],
            ]),
            'pro' => $this->planInput($billingConfig, 'pro'),
            'enterprise' => $this->planInput($billingConfig, 'enterprise'),
        ]);

        $this->assertSame([
            PlanFeature::Employees->label(),
            PlanFeature::Leave->label(),
            PlanFeature::Payroll->label(),
        ], $billingConfig->plan('starter')['features']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(PlanFeature::Payroll->label(), false);
    }

    public function test_enterprise_plan_includes_all_features(): void
    {
        $result = $this->registerOwner();
        $organization = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Enterprise);
        $billing = app(OrganizationBillingService::class);

        foreach (PlanFeature::cases() as $feature) {
            $this->assertTrue($billing->hasFeature($organization, $feature));
        }
    }

    public function test_has_payroll_follows_enabled_features_flag(): void
    {
        $result = $this->registerOwner();
        $billing = app(OrganizationBillingService::class);

        $starter = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);
        $this->assertFalse($billing->hasPayroll($starter));

        $pro = $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);
        $this->assertTrue($billing->hasPayroll($pro));
    }

    /**
     * @param  list<string>  $features
     */
    protected function configureStarterFeatures(array $features): void
    {
        $billingConfig = app(BillingConfigurationService::class);
        $payload = [
            'trial_days' => 14,
            'plans' => $this->fullPlanPayload($billingConfig),
        ];
        $payload['plans']['starter']['enabled_features'] = $features;

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.billing.update'), $payload)
            ->assertRedirect(route('admin.billing.edit'));
    }

    /**
     * @return array{user: \App\Models\User, organization: \App\Models\Organization}
     */
    protected function registerOwner(): array
    {
        return app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner-'.uniqid().'@acme.test',
            'password123',
            'Acme SHPK',
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fullPlanPayload(BillingConfigurationService $billing): array
    {
        $payload = [];

        foreach ($billing->configurablePlanKeys() as $key) {
            $payload[$key] = $this->planInput($billing, $key);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function planInput(BillingConfigurationService $billing, string $key): array
    {
        $plan = $billing->plan($key);

        return [
            'name' => $plan['name'],
            'employee_limit' => $plan['employee_limit'],
            'price_label' => $plan['price_label'],
            'monthly_price' => $plan['monthly_price'],
            'stripe_price_id' => $plan['stripe_price_id'],
            'paypal_plan_id' => $plan['paypal_plan_id'] ?? null,
            'enabled_features' => $plan['enabled_features'] ?? [],
        ];
    }
}
