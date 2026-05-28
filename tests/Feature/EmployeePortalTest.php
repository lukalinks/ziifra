<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{organization: \App\Models\Organization, user: User, employee: Employee}
     */
    protected function linkedEmployeeSetup(): array
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($owner['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
        ]);

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        return [
            'organization' => $owner['organization'],
            'user' => $employeeUser,
            'employee' => $employee,
        ];
    }

    public function test_employee_dashboard_renders_portal_shell_with_profile(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('dashboard', $setup['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.header'), false)
            ->assertSee(__('employee_dashboard.quick_access'), false)
            ->assertSee(__('employee_dashboard.request_leave'), false)
            ->assertSee('ziifra-portal-employee', false)
            ->assertSee('ziifra-dashboard-employee', false)
            ->assertSee('ziifra-portal-top', false)
            ->assertSee('ziifra-portal-kpis', false)
            ->assertSee('ziifra-mobile-tabbar', false)
            ->assertSee('id="ziifra-mobile-nav"', false)
            ->assertSee('data-mobile-nav-open', false)
            ->assertSee('id="ziifra-confirm-dialog"', false)
            ->assertSee('id="ziifra-page-loader"', false)
            ->assertSee(__('navigation.leave'), false)
            ->assertSee(__('navigation.chat'), false)
            ->assertDontSee(__('admin_dashboard.header'), false);
    }

    public function test_employee_dashboard_without_profile_shows_link_hint(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create();
        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.profile_link_hint'), false)
            ->assertDontSee('ziifra-btn-primary', false)
            ->assertDontSee(__('employee_dashboard.quick_access'), false);
    }

    public function test_employee_dashboard_shows_pending_badge(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $leaveType = LeaveType::query()
            ->where('organization_id', $setup['organization']->id)
            ->first();

        LeaveRequest::factory()
            ->forEmployee($setup['employee'], $leaveType, $setup['user'])
            ->create([
                'status' => LeaveRequestStatus::Pending,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-02',
                'days' => 2,
            ]);

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('dashboard', $setup['organization']))
            ->assertOk()
            ->assertSee(trans_choice('employee_dashboard.pending_requests_count', 1, ['count' => 1]), false);
    }

    public function test_employee_leave_index_renders_mobile_cards(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $leaveType = LeaveType::query()
            ->where('organization_id', $setup['organization']->id)
            ->first();

        LeaveRequest::factory()
            ->forEmployee($setup['employee'], $leaveType, $setup['user'])
            ->create([
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-05',
                'days' => 5,
            ]);

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('leave.index', $setup['organization']))
            ->assertOk()
            ->assertSee(__('leave.my_header'), false)
            ->assertSee('ziifra-list-card', false)
            ->assertSee($leaveType->name, false)
            ->assertSee(__('leave.all_statuses'), false);
    }

    public function test_employee_can_access_self_service_modules(): void
    {
        $setup = $this->linkedEmployeeSetup();
        $session = ['current_organization_id' => $setup['organization']->id];

        foreach (['leave.index', 'leave.create', 'expenses.index', 'time.index', 'chat.index'] as $route) {
            $this->actingAs($setup['user'])
                ->withSession($session)
                ->get($this->workspaceRoute($route, $setup['organization']))
                ->assertOk("Employee should access {$route}");
        }
    }

    public function test_employee_cannot_access_admin_modules(): void
    {
        $setup = $this->linkedEmployeeSetup();
        $this->useOrganizationPlan($setup['organization'], SubscriptionPlan::Pro);
        $session = ['current_organization_id' => $setup['organization']->id];

        foreach (['employees.index', 'settings.index', 'reports.index', 'payroll.index', 'invoices.index'] as $route) {
            $this->actingAs($setup['user'])
                ->withSession($session)
                ->get($this->workspaceRoute($route, $setup['organization']))
                ->assertForbidden("Employee should not access {$route}");
        }
    }

    public function test_employee_can_cancel_pending_leave_from_detail_page(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $leaveType = LeaveType::query()
            ->where('organization_id', $setup['organization']->id)
            ->first();

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->post($this->workspaceRoute('leave.store', $setup['organization']), [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-03',
            ])
            ->assertRedirect();

        $request = LeaveRequest::query()->where('employee_id', $setup['employee']->id)->first();
        $this->assertNotNull($request);

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('leave.show', $setup['organization'], ['leaveRequest' => $request]))
            ->assertOk()
            ->assertSee('data-confirm="'.__('leave.confirm_cancel').'"', false);

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->post($this->workspaceRoute('leave.cancel', $setup['organization'], ['leaveRequest' => $request]))
            ->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
    }

    public function test_employee_without_profile_cannot_access_leave_but_sees_hint_on_dashboard(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['email' => 'unlinked@acme.test']);
        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.index', $owner['organization']))
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.profile_link_hint'), false)
            ->assertDontSee(__('navigation.leave'), false);
    }

    public function test_employee_is_auto_linked_by_email_on_workspace_visit(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($owner['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => null,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.request_leave'), false)
            ->assertSee(__('navigation.leave'), false);

        $this->assertDatabaseHas('employees', [
            'organization_id' => $owner['organization']->id,
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);
    }

    public function test_employee_can_view_own_profile(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('employees.show', $setup['organization'], ['employee' => $setup['employee']]))
            ->assertOk()
            ->assertSee($setup['employee']->fullName(), false)
            ->assertSee(__('navigation.dashboard'), false)
            ->assertDontSee(__('employees.back_to_list'), false);
    }

    public function test_employee_cannot_view_other_employee_profile(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $other = Employee::factory()->forOrganization($setup['organization'])->create();

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('employees.show', $setup['organization'], ['employee' => $other]))
            ->assertForbidden();
    }

    public function test_employee_navigation_includes_self_service_modules_on_starter_plan(): void
    {
        $setup = $this->linkedEmployeeSetup();

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id])
            ->get($this->workspaceRoute('dashboard', $setup['organization']))
            ->assertOk()
            ->assertSee(__('navigation.my_profile'), false)
            ->assertSee(__('employee_dashboard.shortcut_time'), false)
            ->assertSee(__('employee_dashboard.shortcut_expenses'), false)
            ->assertSee(__('employee_dashboard.view_profile'), false);
    }

    public function test_employee_leave_create_page_is_translated_in_german(): void
    {
        $setup = $this->linkedEmployeeSetup();
        $setup['user']->update(['locale' => 'de']);

        $this->actingAs($setup['user'])
            ->withSession(['current_organization_id' => $setup['organization']->id, 'locale' => 'de'])
            ->get($this->workspaceRoute('leave.create', $setup['organization']))
            ->assertOk()
            ->assertSee(__('leave.create.title_self', [], 'de'), false)
            ->assertSee(__('leave.create.submit', [], 'de'), false);
    }
}
