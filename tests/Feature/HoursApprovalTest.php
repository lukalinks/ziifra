<?php

namespace Tests\Feature;

use App\Enums\DailyHoursApprovalStatus;
use App\Enums\SubscriptionPlan;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Services\PayrollTimeService;
use App\Services\RegisterOrganizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoursApprovalTest extends TestCase
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

    public function test_employee_upsert_creates_pending_hours(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site A',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create(['email' => 'worker@acme.test']);
        $organization->users()->attach($employeeUser->id, ['role' => 'employee', 'joined_at' => now()]);

        $employee = Employee::factory()->forOrganization($organization)->create([
            'email' => 'worker@acme.test',
            'user_id' => $employeeUser->id,
        ]);
        $project->members()->attach($employee->id);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('my-hours.upsert', $organization), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'work_date' => now()->toDateString(),
                'hours' => 6,
            ])
            ->assertOk()
            ->assertJsonPath('approval_status', 'pending');

        $entry = DailyHoursEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame(DailyHoursApprovalStatus::Pending, $entry->approval_status);
    }

    public function test_payroll_grid_totals_use_only_approved_hours(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site B',
            'status' => 'active',
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create([
            'compensation_type' => 'hourly',
            'fixed_hourly_rate' => 10,
        ]);
        $project->members()->attach($employee->id);

        $date = Carbon::now()->startOfMonth()->toDateString();

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => $date,
            'hours' => 8,
            'approval_status' => DailyHoursApprovalStatus::Approved,
        ]);

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => Carbon::parse($date)->addDay()->toDateString(),
            'hours' => 4,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);

        $grid = app(PayrollTimeService::class)->grid(
            $organization,
            (int) now()->year,
            (int) now()->month,
            $project->id,
        );

        $row = $grid['rows'][0];
        $this->assertSame(8.0, $row['total_hours']);
        $this->assertSame(4.0, $row['pending_hours']);
        $this->assertSame('pending', $row['row_status']);
        $this->assertSame(8.0, $grid['totals']['hours']);
        $this->assertSame(4.0, $grid['totals']['pending_hours']);
    }

    public function test_admin_can_approve_hours_from_payroll_time(): void
    {
        $result = $this->proWorkspace();
        $organization = $result['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Site C',
            'status' => 'active',
        ]);

        $employee = Employee::factory()->forOrganization($organization)->create();
        $project->members()->attach($employee->id);

        $workDate = now()->startOfMonth();

        DailyHoursEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => $workDate->toDateString(),
            'hours' => 5,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll-time.hours.approve', $organization, [
                'employee' => $employee,
            ]), [
                'year' => $workDate->year,
                'month' => $workDate->month,
                'project_id' => $project->id,
            ])
            ->assertRedirect();

        $entry = DailyHoursEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame(DailyHoursApprovalStatus::Approved, $entry->approval_status);
    }
}
