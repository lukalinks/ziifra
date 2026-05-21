<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\PayrollRunStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_out_today_and_upcoming_leave(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Out',
            'last_name' => 'Today',
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $result['organization']->id)->first();

        LeaveRequest::factory()
            ->forEmployee($employee, $leaveType, $result['user'])
            ->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'days' => 1,
                'status' => LeaveRequestStatus::Approved,
            ]);

        $upcoming = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Soon',
            'last_name' => 'Away',
        ]);

        LeaveRequest::factory()
            ->forEmployee($upcoming, $leaveType, $result['user'])
            ->create([
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'days' => 3,
                'status' => LeaveRequestStatus::Approved,
            ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee('Out today', false)
            ->assertSee('Soon Away', false)
            ->assertSee('Leave this week', false);
    }

    public function test_dashboard_shows_expiring_document_count(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => \App\Enums\EmployeeDocumentType::Contract,
            'title' => 'Passport',
            'file_path' => 'test/path.pdf',
            'original_filename' => 'passport.pdf',
            'expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee('Documents expiring', false)
            ->assertSee('Passport', false);
    }

    public function test_dashboard_shows_recent_hire_and_draft_payroll(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        Employee::factory()->forOrganization($organization)->create([
            'first_name' => 'New',
            'last_name' => 'Starter',
            'start_date' => now()->subDays(3),
        ]);

        PayrollRun::query()->create([
            'organization_id' => $organization->id,
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'status' => PayrollRunStatus::Draft,
            'rules_snapshot' => config('payroll.kosovo'),
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('dashboard', $organization))
            ->assertOk()
            ->assertSee('Recent hires', false)
            ->assertSee('New Starter', false)
            ->assertSee('Payroll draft open', false);
    }
}
