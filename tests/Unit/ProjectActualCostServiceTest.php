<?php

namespace Tests\Unit;

use App\Enums\CompensationType;
use App\Enums\DailyHoursApprovalStatus;
use App\Enums\ProjectDocumentCategory;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\EmployeeHourlyRate;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Enums\SubscriptionPlan;
use App\Services\ProjectActualCostService;
use App\Services\RegisterOrganizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectActualCostServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sums_labor_and_document_amounts_for_project(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $employee = Employee::factory()->forOrganization($organization)->create([
            'compensation_type' => CompensationType::Hourly,
            'fixed_hourly_rate' => 50.00,
        ]);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Baustelle',
            'status' => 'active',
            'budget' => 50000,
            'currency' => 'EUR',
        ]);
        $project->members()->attach($employee->id);

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => Carbon::parse('2026-05-10'),
            'hours' => 10,
            'approval_status' => DailyHoursApprovalStatus::Approved,
        ]);

        EmployeeHourlyRate::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'year' => 2026,
            'month' => 5,
            'hourly_rate' => 40.00,
            'currency' => 'EUR',
        ]);

        ProjectDocument::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'uploaded_by_user_id' => $result['user']->id,
            'category' => ProjectDocumentCategory::Materials,
            'title' => 'Concrete invoice',
            'amount' => 1500.50,
            'file_path' => 'test/invoice.pdf',
            'original_filename' => 'invoice.pdf',
            'uploaded_at' => now(),
        ]);

        $costs = app(ProjectActualCostService::class)->forProject($project->fresh(['documents']));

        $this->assertSame(500.0, $costs['labor']);
        $this->assertSame(1500.5, $costs['documents']);
        $this->assertSame(2000.5, $costs['total']);
        $this->assertSame('- 2\'000.50 .- EUR', $costs['formatted']);
    }

    public function test_project_show_displays_actual_costs_without_budget(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $this->useOrganizationPlan($organization, SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($organization)->create([
            'compensation_type' => CompensationType::Hourly,
            'fixed_hourly_rate' => 25.00,
        ]);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'No budget site',
            'status' => 'active',
            'budget' => null,
            'currency' => 'EUR',
        ]);
        $project->members()->attach($employee->id);

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => now()->toDateString(),
            'hours' => 4,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('projects.show', $organization, ['project' => $project->fresh()]))
            ->assertOk()
            ->assertSee('Actual Costs for this Project: - 100.00 .- EUR', false);
    }

    public function test_project_show_displays_actual_spendings_with_budget(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $this->useOrganizationPlan($organization, SubscriptionPlan::Starter);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Baustelle',
            'status' => 'active',
            'budget' => 50000,
            'currency' => 'EUR',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('projects.show', $organization, ['project' => $project->fresh()]))
            ->assertOk()
            ->assertSee('Actual Spendings: - 0.00 .- EUR total in Euro', false);
    }
}
