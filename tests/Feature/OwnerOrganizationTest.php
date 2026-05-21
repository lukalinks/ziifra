<?php

namespace Tests\Feature;

use App\Enums\ExpenseClaimStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\ProjectStatus;
use App\Enums\SubscriptionPlan;
use App\Models\ChatMessage;
use App\Models\Project;
use App\Services\DemoDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class OwnerOrganizationTest extends TestCase
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
    protected function createAndSettingsRoutes(): array
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
            'settings.company.edit',
            'settings.departments.index',
            'settings.positions.index',
            'settings.employee-fields.index',
            'settings.leave-types.index',
        ];
    }

    public function test_owner_can_access_all_workspace_modules(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        foreach ($this->indexRoutes() as $routeName) {
            $this->actingAsOwner($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Owner cannot access {$routeName}.");
        }

        foreach ($this->createAndSettingsRoutes() as $routeName) {
            $this->actingAsOwner($demo)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Owner cannot access {$routeName}.");
        }
    }

    public function test_owner_can_view_demo_entity_pages(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('employees.show', $organization, ['employee' => $demo['employees'][0]]))
            ->assertOk()
            ->assertSee('Mira', false);

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('invoices.show', $organization, ['invoice' => $demo['invoice']]))
            ->assertOk()
            ->assertSee('Beta Client SHPK', false);

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('expenses.show', $organization, ['expenseClaim' => $demo['expense_claim']]))
            ->assertOk()
            ->assertSee('Client visit taxi', false);

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('projects.show', $organization, ['project' => $demo['project']]))
            ->assertOk()
            ->assertSee('Website refresh', false);

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('payroll.show', $organization, ['payrollRun' => $demo['payroll_run']]))
            ->assertOk();

        if ($demo['leave_request'] !== null) {
            $this->actingAsOwner($demo)
                ->get($this->workspaceRoute('leave.show', $organization, ['leaveRequest' => $demo['leave_request']]))
                ->assertOk()
                ->assertSee('Family visit', false);
        }
    }

    public function test_owner_can_approve_leave_and_expense(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        if ($demo['leave_request'] !== null) {
            $this->actingAsOwner($demo)
                ->post($this->workspaceRoute('leave.approve', $organization, [
                    'leaveRequest' => $demo['leave_request'],
                ]))
                ->assertRedirect();

            $this->assertSame(LeaveRequestStatus::Approved, $demo['leave_request']->fresh()->status);
        }

        $this->actingAsOwner($demo)
            ->post($this->workspaceRoute('expenses.approve', $organization, [
                'expenseClaim' => $demo['expense_claim'],
            ]))
            ->assertRedirect();

        $this->assertSame(ExpenseClaimStatus::Approved, $demo['expense_claim']->fresh()->status);
    }

    public function test_owner_can_create_project_and_post_chat(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsOwner($demo)
            ->post($this->workspaceRoute('projects.store', $organization), [
                'name' => 'Owner initiative',
                'description' => 'New product launch',
                'status' => ProjectStatus::Planning->value,
                'employee_ids' => [$demo['employees'][0]->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'Owner initiative']);

        $this->actingAsOwner($demo)
            ->post($this->workspaceRoute('chat.store', $organization), [
                'body' => 'Owner checking in with the team.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'organization_id' => $organization->id,
            'user_id' => $demo['owner']->id,
            'body' => 'Owner checking in with the team.',
        ]);
    }

    public function test_owner_can_export_employees_and_update_company_settings(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $this->actingAsOwner($demo)
            ->get($this->workspaceRoute('employees.export', $organization))
            ->assertOk();

        $this->actingAsOwner($demo)
            ->put($this->workspaceRoute('settings.company.update', $organization), $this->companySettingsPayload($organization, [
                'name' => 'Demo Corporation Updated',
            ]))
            ->assertRedirect();

        $this->assertSame('Demo Corporation Updated', $organization->fresh()->name);
    }

    public function test_owner_cannot_access_platform_admin(): void
    {
        $demo = $this->seedDemoOrganization();

        $this->actingAsOwner($demo)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAsOwner($demo)
            ->get('/admin/organizations')
            ->assertForbidden();
    }

    public function test_demo_data_includes_owner_workspace_entities(): void
    {
        $demo = app(DemoDataService::class)->seed();

        $this->assertSame(SubscriptionPlan::Pro, $demo['organization']->plan);
        $this->assertGreaterThanOrEqual(3, count($demo['employees']));
        $this->assertSame('Operations', $demo['department']->name);
        $this->assertSame(3, $demo['payroll_run']->items()->count());
        $this->assertInstanceOf(Project::class, $demo['project']);
        $this->assertGreaterThanOrEqual(2, ChatMessage::query()->count());
    }
}
