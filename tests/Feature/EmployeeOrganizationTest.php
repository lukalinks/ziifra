<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TimeEntry;
use App\Services\DemoDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class EmployeeOrganizationTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    /**
     * @return list<string>
     */
    protected function allowedRoutes(): array
    {
        return [
            'dashboard',
            'leave.index',
            'leave.create',
            'leave.calendar',
            'expenses.index',
            'expenses.create',
            'time.index',
            'chat.index',
        ];
    }

    /**
     * @return list<string>
     */
    protected function forbiddenRoutes(): array
    {
        return [
            'employees.index',
            'documents.index',
            'invoices.index',
            'payroll.index',
            'projects.index',
            'reports.index',
            'settings.index',
            'team.index',
        ];
    }

    public function test_employee_can_access_self_service_modules(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        foreach ($this->allowedRoutes() as $routeName) {
            $this->actingAsEmployee($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Employee cannot access {$routeName}.");
        }
    }

    public function test_employee_cannot_access_hr_and_admin_modules(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        foreach ($this->forbiddenRoutes() as $routeName) {
            $this->actingAsEmployee($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertForbidden("Employee should not access {$routeName}.");
        }

        $this->actingAsEmployee($demo)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAsEmployee($demo)
            ->get($this->workspaceRoute('employees.create', $organization))
            ->assertForbidden();
    }

    public function test_employee_sees_personal_dashboard(): void
    {
        $demo = $this->seedDemoOrganization();

        $this->actingAsEmployee($demo)
            ->get($this->workspaceRoute('dashboard', $demo['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.header'), false)
            ->assertSee(__('employee_dashboard.my_requests'), false)
            ->assertDontSee(__('admin_dashboard.pending_approvals'), false);
    }

    public function test_employee_can_view_own_leave_and_expense(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        if ($demo['leave_request'] !== null) {
            $this->actingAsEmployee($demo)
                ->get($this->workspaceRoute('leave.show', $organization, [
                    'leaveRequest' => $demo['leave_request'],
                ]))
                ->assertOk()
                ->assertSee('Family visit', false);
        }

        $this->actingAsEmployee($demo)
            ->get($this->workspaceRoute('expenses.show', $organization, [
                'expenseClaim' => $demo['expense_claim'],
            ]))
            ->assertOk()
            ->assertSee('Client visit taxi', false);
    }

    public function test_employee_cannot_view_colleague_leave_or_approve_requests(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];
        $manager = $demo['employees'][0];

        $leaveType = LeaveType::query()->where('organization_id', $organization->id)->firstOrFail();

        $managerLeave = LeaveRequest::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $manager->id,
            'leave_type_id' => $leaveType->id,
            'submitted_by_user_id' => $demo['owner']->id,
            'start_date' => now()->addMonths(3)->startOfMonth(),
            'end_date' => now()->addMonths(3)->startOfMonth()->addDays(1),
            'days' => 2,
            'status' => LeaveRequestStatus::Pending,
        ]);

        $this->actingAsEmployee($demo)
            ->get($this->workspaceRoute('leave.show', $organization, [
                'leaveRequest' => $managerLeave,
            ]))
            ->assertForbidden();

        if ($demo['leave_request'] !== null) {
            $this->actingAsEmployee($demo)
                ->post($this->workspaceRoute('leave.approve', $organization, [
                    'leaveRequest' => $demo['leave_request'],
                ]))
                ->assertForbidden();
        }

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('expenses.approve', $organization, [
                'expenseClaim' => $demo['expense_claim'],
            ]))
            ->assertForbidden();
    }

    public function test_employee_can_request_leave_and_submit_expense(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $leaveType = LeaveType::query()->where('organization_id', $organization->id)->firstOrFail();

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('leave.store', $organization), [
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->addMonths(2)->format('Y-m-d'),
                'end_date' => now()->addMonths(2)->addDays(1)->format('Y-m-d'),
                'notes' => 'Personal day',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $demo['staff']->id,
            'notes' => 'Personal day',
            'status' => LeaveRequestStatus::Pending->value,
        ]);

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('expenses.store', $organization), [
                'category' => ExpenseCategory::Travel->value,
                'title' => 'Bus ticket',
                'amount' => 12.50,
                'expense_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_claims', [
            'employee_id' => $demo['staff']->id,
            'title' => 'Bus ticket',
        ]);
    }

    public function test_employee_can_clock_in_out_and_post_chat(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        TimeEntry::query()
            ->where('employee_id', $demo['staff']->id)
            ->whereNull('clock_out')
            ->update(['clock_out' => now()]);

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('time.clock-in', $organization))
            ->assertRedirect();

        $entry = TimeEntry::query()
            ->where('employee_id', $demo['staff']->id)
            ->whereNull('clock_out')
            ->first();

        $this->assertNotNull($entry);

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('time.clock-out', $organization))
            ->assertRedirect();

        $this->assertNotNull($entry->fresh()->clock_out);

        $this->actingAsEmployee($demo)
            ->post($this->workspaceRoute('chat.store', $organization), [
                'body' => 'Thanks team — submitted my leave request.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $demo['employee_user']->id,
            'body' => 'Thanks team — submitted my leave request.',
        ]);
    }

    public function test_employee_navigation_includes_leave_expenses_time_and_chat(): void
    {
        $demo = $this->seedDemoOrganization();
        $nav = app(\App\Support\WorkspaceNavigation::class);
        $groups = $nav->groups($demo['organization'], $demo['employee_user']);

        $itemLabels = collect($groups)->flatMap(fn (array $g) => array_column($g['items'], 'label'))->all();

        $this->assertContains(__('navigation.leave'), $itemLabels);
        $this->assertContains(__('navigation.expenses'), $itemLabels);
        $this->assertContains(__('navigation.time_and_attendance'), $itemLabels);
        $this->assertContains(__('navigation.chat'), $itemLabels);
        $this->assertNotContains(__('navigation.employees'), $itemLabels);
        $this->assertNotContains(__('navigation.reports'), $itemLabels);
    }

    public function test_demo_data_includes_linked_employee_profile(): void
    {
        $demo = app(DemoDataService::class)->seed();

        $this->assertNotNull($demo['employee_user']);
        $this->assertNotNull($demo['staff']);
        $this->assertSame($demo['employee_user']->id, $demo['staff']->user_id);
        $this->assertSame('employee@demo.test', $demo['staff']->email);
    }
}
