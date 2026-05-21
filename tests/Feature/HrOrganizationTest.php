<?php

namespace Tests\Feature;

use App\Enums\CustomFieldType;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseClaimStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Enums\ProjectStatus;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TimeEntry;
use App\Services\DemoDataService;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class HrOrganizationTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    /**
     * @return list<string>
     */
    protected function indexRoutes(): array
    {
        return [
            'dashboard',
            'employees.index',
            'documents.index',
            'leave.index',
            'payroll.index',
            'invoices.index',
            'expenses.index',
            'projects.index',
            'time.index',
            'reports.index',
            'chat.index',
            'settings.index',
            'team.index',
            'leave.calendar',
        ];
    }

    /**
     * @return list<string>
     */
    protected function hrAllowedCreateRoutes(): array
    {
        return [
            'employees.create',
            'employees.import',
            'invoices.create',
            'expenses.create',
            'projects.create',
            'leave.create',
            'payroll.create',
            'settings.billing',
            'settings.departments.index',
            'settings.positions.index',
            'settings.leave-types.index',
        ];
    }

    public function test_hr_can_access_all_workspace_modules(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        foreach ($this->indexRoutes() as $routeName) {
            $this->actingAsHr($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("HR cannot access {$routeName}.");
        }

        foreach ($this->hrAllowedCreateRoutes() as $routeName) {
            $this->actingAsHr($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("HR cannot access {$routeName}.");
        }
    }

    public function test_hr_sees_admin_dashboard(): void
    {
        $demo = $this->seedDemoOrganization();

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('dashboard', $demo['organization']))
            ->assertOk()
            ->assertSee(__('admin_dashboard.header'), false)
            ->assertSee(__('admin_dashboard.pending_approvals'), false);
    }

    public function test_hr_cannot_access_company_settings_or_platform_admin(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('settings.company.edit', $organization))
            ->assertForbidden();

        $this->actingAsHr($demo)
            ->put($this->workspaceRoute('settings.company.update', $organization), [
                'name' => 'Hacked name',
            ])
            ->assertForbidden();

        $this->actingAsHr($demo)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('settings.employee-fields.index', $organization))
            ->assertForbidden();

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('settings.employee-fields.store', $organization), [
                'name' => 'Unauthorized field',
                'type' => CustomFieldType::Text->value,
            ])
            ->assertForbidden();
    }

    public function test_hr_can_view_demo_entity_pages(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('employees.show', $organization, ['employee' => $demo['employees'][0]]))
            ->assertOk();

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('invoices.show', $organization, ['invoice' => $demo['invoice']]))
            ->assertOk()
            ->assertSee('Beta Client SHPK', false);

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('expenses.show', $organization, ['expenseClaim' => $demo['expense_claim']]))
            ->assertOk();

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('projects.show', $organization, ['project' => $demo['project']]))
            ->assertOk();

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('payroll.show', $organization, ['payrollRun' => $demo['payroll_run']]))
            ->assertOk();

        if ($demo['leave_request'] !== null) {
            $this->actingAsHr($demo)
                ->get($this->workspaceRoute('leave.show', $organization, ['leaveRequest' => $demo['leave_request']]))
                ->assertOk()
                ->assertSee('Family visit', false);
        }
    }

    public function test_hr_can_approve_leave_and_expense(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        if ($demo['leave_request'] !== null) {
            $this->actingAsHr($demo)
                ->post($this->workspaceRoute('leave.approve', $organization, [
                    'leaveRequest' => $demo['leave_request'],
                ]))
                ->assertRedirect();

            $this->assertSame(LeaveRequestStatus::Approved, $demo['leave_request']->fresh()->status);
        }

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('expenses.approve', $organization, [
                'expenseClaim' => $demo['expense_claim'],
            ]))
            ->assertRedirect();

        $this->assertSame(ExpenseClaimStatus::Approved, $demo['expense_claim']->fresh()->status);
    }

    public function test_hr_can_create_employee_submit_leave_and_expense(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('employees.store', $organization), [
                'first_name' => 'Ana',
                'last_name' => 'Berisha',
                'email' => 'ana@demo.test',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
            ])
            ->assertRedirect();

        $newEmployee = Employee::query()->where('email', 'ana@demo.test')->first();
        $this->assertNotNull($newEmployee);

        $leaveType = LeaveType::query()->where('organization_id', $organization->id)->firstOrFail();

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('leave.store', $organization), [
                'employee_id' => $newEmployee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->addMonth()->format('Y-m-d'),
                'end_date' => now()->addMonth()->addDays(2)->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $newEmployee->id,
            'status' => LeaveRequestStatus::Pending->value,
        ]);

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('expenses.store', $organization), [
                'employee_id' => $newEmployee->id,
                'category' => ExpenseCategory::Office->value,
                'title' => 'Desk supplies',
                'amount' => 45,
                'expense_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_claims', [
            'employee_id' => $newEmployee->id,
            'title' => 'Desk supplies',
        ]);
    }

    public function test_hr_can_manage_projects_time_and_chat(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];
        $staff = $demo['employees'][1];

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('projects.store', $organization), [
                'name' => 'HR onboarding project',
                'status' => ProjectStatus::Active->value,
                'employee_ids' => [$staff->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'HR onboarding project']);

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('time.clock-in', $organization), [
                'employee_id' => $staff->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'employee_id' => $staff->id,
            'clock_out' => null,
        ]);

        $entry = TimeEntry::query()->where('employee_id', $staff->id)->whereNull('clock_out')->first();
        $this->assertNotNull($entry);

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('time.clock-out', $organization), [
                'employee_id' => $staff->id,
            ])
            ->assertRedirect();

        $this->assertNotNull($entry->fresh()->clock_out);

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('chat.store', $organization), [
                'body' => 'HR team update: policies refreshed.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $demo['hr']->id,
            'body' => 'HR team update: policies refreshed.',
        ]);
    }

    public function test_hr_can_invite_team_member(): void
    {
        Mail::fake();

        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('team.invitations.store', $organization), [
                'email' => 'newhr@demo.test',
                'role' => OrganizationRole::Manager->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $organization->id,
            'email' => 'newhr@demo.test',
            'role' => OrganizationRole::Manager->value,
        ]);
    }

    public function test_hr_can_export_employees_and_update_departments(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsHr($demo)
            ->get($this->workspaceRoute('employees.export', $organization))
            ->assertOk();

        $this->actingAsHr($demo)
            ->post($this->workspaceRoute('settings.departments.store', $organization), [
                'name' => 'People Ops',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'organization_id' => $organization->id,
            'name' => 'People Ops',
        ]);
    }

    public function test_demo_data_includes_hr_profile(): void
    {
        $demo = app(DemoDataService::class)->seed();

        $this->assertNotNull($demo['hr']);
        $this->assertDatabaseHas('employees', [
            'organization_id' => $demo['organization']->id,
            'email' => 'hr@demo.test',
            'user_id' => $demo['hr']->id,
        ]);
    }
}
