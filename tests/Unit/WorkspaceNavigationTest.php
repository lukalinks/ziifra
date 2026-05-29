<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\User;
use App\Services\OrganizationBillingService;
use App\Services\RegisterOrganizationService;
use App\Support\WorkspaceNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WorkspaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_all_navigation_groups_including_coming_soon(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $billing = Mockery::mock(OrganizationBillingService::class);
        $billing->shouldReceive('hasFeature')->andReturn(true);
        $this->app->instance(OrganizationBillingService::class, $billing);

        $nav = app(WorkspaceNavigation::class);
        $groups = $nav->groups($result['organization'], $result['user']);

        $labels = array_column($groups, 'label');

        $this->assertSame([
            __('navigation.primary'),
            __('navigation.people'),
            __('navigation.insights'),
            __('navigation.collaborate'),
            __('navigation.admin'),
        ], $labels);

        $itemLabels = collect($groups)->flatMap(fn (array $g) => array_column($g['items'], 'label'))->all();

        $this->assertContains(__('navigation.dashboard'), $itemLabels);
        $this->assertContains(__('navigation.invoices'), $itemLabels);
        $this->assertContains(__('navigation.projects'), $itemLabels);
        $this->assertContains(__('navigation.payroll_and_time'), $itemLabels);
        $this->assertContains(__('navigation.reports'), $itemLabels);
        $this->assertContains(__('navigation.chat'), $itemLabels);
        $this->assertNotContains(__('navigation.expenses'), $itemLabels);
        $this->assertNotContains(__('navigation.time_and_attendance'), $itemLabels);
        $this->assertNotContains(__('navigation.project_documents'), $itemLabels);

        $comingSoon = collect($groups)
            ->flatMap(fn (array $g) => $g['items'])
            ->filter(fn (array $item) => $item['coming_soon'])
            ->pluck('label')
            ->all();

        $this->assertSame([], $comingSoon);
    }

    public function test_unlinked_employee_sees_dashboard_and_chat_only(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $nav = app(WorkspaceNavigation::class);
        $groups = $nav->groups($result['organization'], $employeeUser);

        $labels = array_column($groups, 'label');

        $this->assertSame([
            __('navigation.primary'),
            __('navigation.collaborate'),
        ], $labels);

        $itemLabels = collect($groups)->flatMap(fn (array $g) => array_column($g['items'], 'label'))->all();

        $this->assertNotContains(__('navigation.leave'), $itemLabels);
        $this->assertContains(__('navigation.chat'), $itemLabels);
    }

    public function test_linked_employee_sees_leave_and_self_service_modules(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        Employee::factory()->forOrganization($result['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $nav = app(WorkspaceNavigation::class);
        $groups = $nav->groups($result['organization'], $employeeUser);

        $labels = array_column($groups, 'label');

        $this->assertSame([
            __('navigation.primary'),
            __('navigation.people'),
            __('navigation.collaborate'),
        ], $labels);

        $itemLabels = collect($groups)->flatMap(fn (array $g) => array_column($g['items'], 'label'))->all();

        $this->assertContains(__('navigation.leave'), $itemLabels);
        $this->assertContains(__('navigation.chat'), $itemLabels);
        $this->assertContains(__('navigation.expenses'), $itemLabels);
        $this->assertContains(__('navigation.time_and_attendance'), $itemLabels);
        $this->assertNotContains(__('navigation.employees'), $itemLabels);
        $this->assertNotContains(__('navigation.reports'), $itemLabels);
        $this->assertNotContains(__('navigation.payroll'), $itemLabels);
        $this->assertNotContains(__('navigation.projects'), $itemLabels);
    }

    public function test_flat_navigation_omits_coming_soon_items(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $nav = app(WorkspaceNavigation::class);
        $flat = $nav->flat($result['organization'], $result['user']);

        foreach ($flat as $item) {
            $this->assertTrue($item['enabled']);
            $this->assertFalse($item['coming_soon']);
            $this->assertNotNull($item['route']);
        }

        $flatLabels = array_column($flat, 'label');

        $this->assertContains(__('navigation.documents'), $flatLabels);
        $this->assertContains(__('navigation.payroll_and_time'), $flatLabels);
        $payrollIndex = array_search(__('navigation.payroll_and_time'), $flatLabels, true);
        $documentsIndex = array_search(__('navigation.documents'), $flatLabels, true);
        $this->assertNotFalse($payrollIndex);
        $this->assertNotFalse($documentsIndex);
        $this->assertTrue($payrollIndex < $documentsIndex, 'Payroll should appear before Documents in the nav.');
        $this->assertContains(__('navigation.chat'), $flatLabels);
        $this->assertNotContains(__('navigation.project_documents'), $flatLabels);
    }

    public function test_primary_mobile_navigation_limits_tab_bar_items(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $billing = Mockery::mock(OrganizationBillingService::class);
        $billing->shouldReceive('hasFeature')->andReturn(true);
        $this->app->instance(OrganizationBillingService::class, $billing);

        $nav = app(WorkspaceNavigation::class);
        $primary = $nav->primaryMobile($result['organization'], $result['user']);

        $this->assertCount(3, $primary);
        $this->assertSame('dashboard', $primary[0]['route']);
    }
}
