<?php

namespace Tests\Feature;

use App\Enums\DailyHoursApprovalStatus;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\PayrollTimeService;
use App\Services\ProjectActualCostService;
use App\Services\RegisterOrganizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFeaturesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: \App\Models\User, organization: \App\Models\Organization}
     */
    protected function proWorkspace(): array
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        return $result;
    }

    public function test_admin_dashboard_shows_quick_actions_and_french_locale(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];
        $organization->update(['locale' => 'fr']);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('dashboard', $organization))
            ->assertOk()
            ->assertSee(__('dashboard.quick_actions', [], 'fr'), false)
            ->assertSee('grid-cols-1', false);
    }

    public function test_project_show_displays_actual_spendings_and_document_amounts(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $employee = Employee::factory()->forOrganization($organization)->create([
            'compensation_type' => 'hourly',
            'fixed_hourly_rate' => 20,
        ]);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Baustelle Pratteln',
            'status' => 'active',
            'budget' => 50000,
            'currency' => 'EUR',
        ]);
        $project->members()->attach($employee->id);

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => now()->startOfMonth()->toDateString(),
            'hours' => 10,
            'approval_status' => DailyHoursApprovalStatus::Approved,
        ]);

        ProjectDocument::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'uploaded_by_user_id' => $result['user']->id,
            'category' => 'materials',
            'title' => 'Concrete',
            'amount' => 500,
            'file_path' => 'test/inv.pdf',
            'original_filename' => 'inv.pdf',
            'uploaded_at' => now(),
        ]);

        $costs = app(ProjectActualCostService::class)->forProject($project->fresh(['documents']));
        $this->assertSame(700.0, $costs['total']);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('projects.show', $organization, ['project' => $project]))
            ->assertOk()
            ->assertSee(__('projects.actual_spendings', ['amount' => $costs['formatted']]), false)
            ->assertSee('Baustelle Pratteln', false);
    }

    public function test_payroll_time_page_shows_approval_controls_when_pending(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site',
            'status' => 'active',
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create();
        $project->members()->attach($employee->id);

        $month = now()->startOfMonth();

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => $month->toDateString(),
            'hours' => 7,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payroll-time.index', [
                'organization' => $organization,
                'year' => $month->year,
                'month' => $month->month,
                'project_id' => $project->id,
            ]))
            ->assertOk()
            ->assertSee(__('daily_hours.status_pending'), false)
            ->assertSee(__('daily_hours.approve_row'), false)
            ->assertSee(__('daily_hours.approve_all'), false)
            ->assertSee(__('payroll_time.pending_hours'), false);
    }

    public function test_approve_all_moves_hours_into_approved_totals(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site',
            'status' => 'active',
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create([
            'compensation_type' => 'hourly',
            'fixed_hourly_rate' => 10,
        ]);
        $project->members()->attach($employee->id);

        $month = now()->startOfMonth();

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => $month->toDateString(),
            'hours' => 6,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);

        $before = app(PayrollTimeService::class)->grid(
            $organization,
            $month->year,
            $month->month,
            $project->id,
        );
        $this->assertSame(0.0, $before['totals']['hours']);
        $this->assertSame(6.0, $before['totals']['pending_hours']);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('payroll-time.hours.approve-all', $organization), [
                'year' => $month->year,
                'month' => $month->month,
                'project_id' => $project->id,
            ])
            ->assertRedirect();

        $after = app(PayrollTimeService::class)->grid(
            $organization,
            $month->year,
            $month->month,
            $project->id,
        );
        $this->assertSame(6.0, $after['totals']['hours']);
        $this->assertSame(0.0, $after['totals']['pending_hours']);
        $this->assertSame('approved', $after['rows'][0]['row_status']);
    }

    public function test_employee_can_access_my_hours_and_submit_pending_entry(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Worker Site',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create(['email' => 'field@acme.test']);
        $organization->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create([
            'email' => 'field@acme.test',
            'user_id' => $employeeUser->id,
        ]);
        $project->members()->attach($employee->id);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('my-hours.index', $organization))
            ->assertOk()
            ->assertSee(__('my_hours.title'), false)
            ->assertSee('Worker Site', false);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('my-hours.upsert', $organization), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'work_date' => now()->toDateString(),
                'hours' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('approval_status', 'pending');

        $this->assertSame(
            DailyHoursApprovalStatus::Pending,
            DailyHoursEntry::query()->first()?->approval_status,
        );
    }

    public function test_employee_cannot_access_payroll_time_admin_grid(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $employeeUser = User::factory()->create();
        $organization->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($organization)->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payroll-time.index', $organization))
            ->assertForbidden();
    }

    public function test_editing_approved_hours_resets_to_pending(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site',
            'status' => 'active',
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create();
        $project->members()->attach($employee->id);

        $entry = DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => now()->startOfMonth()->toDateString(),
            'hours' => 5,
            'approval_status' => DailyHoursApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $result['user']->id,
        ]);

        app(\App\Services\DailyHoursService::class)->upsertCell(
            $project,
            $employee,
            Carbon::parse($entry->work_date),
            6,
        );

        $fresh = $entry->fresh();
        $this->assertSame(DailyHoursApprovalStatus::Pending, $fresh->approval_status);
        $this->assertNull($fresh->approved_at);
    }
}
