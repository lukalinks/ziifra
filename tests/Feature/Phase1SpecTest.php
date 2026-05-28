<?php

namespace Tests\Feature;

use App\Enums\DailyHoursApprovalStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InvoiceSource;
use App\Enums\OrganizationRole;
use App\Enums\ProjectDocumentCategory;
use App\Enums\ProjectStatus;
use App\Enums\SubscriptionPlan;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkspaceNavItem;
use App\Services\DailyHoursService;
use App\Services\EmployeeRateService;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase1SpecTest extends TestCase
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

    /**
     * @return array{user: \App\Models\User, organization: \App\Models\Organization, project: Project, employee: Employee}
     */
    protected function projectWithEmployee(): array
    {
        $result = $this->proWorkspace();

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'employment_status' => EmploymentStatus::Active,
            'employee_code' => 'EMP-001',
        ]);

        $project = Project::query()->create([
            'organization_id' => $result['organization']->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Client rollout',
            'status' => ProjectStatus::Active,
        ]);

        $project->members()->attach($employee->id);

        app(EmployeeRateService::class)->upsert($employee, [
            'year' => 2026,
            'month' => 5,
            'hourly_rate' => 25.00,
        ]);

        return array_merge($result, compact('project', 'employee'));
    }

    public function test_daily_hours_upsert_and_approve_row(): void
    {
        $ctx = $this->projectWithEmployee();

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->postJson($this->workspaceRoute('projects.hours.upsert', $ctx['organization'], ['project' => $ctx['project']]), [
                'employee_id' => $ctx['employee']->id,
                'work_date' => '2026-05-10',
                'hours' => 8,
            ])
            ->assertOk()
            ->assertJson([
                'hours' => 8,
                'approval_status' => DailyHoursApprovalStatus::Pending->value,
            ]);

        $entry = DailyHoursEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame('8.00', (string) $entry->hours);

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->post($this->workspaceRoute('projects.hours.approve', $ctx['organization'], [
                'project' => $ctx['project'],
                'employee' => $ctx['employee'],
            ]), ['month' => '2026-05'])
            ->assertRedirect();

        $this->assertSame(DailyHoursApprovalStatus::Approved, $entry->fresh()->approval_status);
    }

    public function test_daily_hours_cell_is_unique_per_employee_project_and_date(): void
    {
        $ctx = $this->projectWithEmployee();
        $hours = app(DailyHoursService::class);

        $hours->upsertCell($ctx['project'], $ctx['employee'], Carbon::parse('2026-05-12'), 4);
        $hours->upsertCell($ctx['project'], $ctx['employee'], Carbon::parse('2026-05-12'), 6);

        $this->assertSame(1, DailyHoursEntry::query()->count());
        $this->assertSame('6.00', (string) DailyHoursEntry::query()->first()->hours);
    }

    public function test_employee_hourly_rate_resolves_by_month(): void
    {
        $ctx = $this->proWorkspace();
        $employee = Employee::factory()->forOrganization($ctx['organization'])->create();
        $rates = app(EmployeeRateService::class);

        $rates->upsert($employee, ['year' => 2026, 'month' => 4, 'hourly_rate' => 20]);
        $rates->upsert($employee, ['year' => 2026, 'month' => 5, 'hourly_rate' => 30]);

        $this->assertSame(20.0, $rates->hourlyRateFor($employee, Carbon::parse('2026-04-15')));
        $this->assertSame(30.0, $rates->hourlyRateFor($employee, Carbon::parse('2026-05-15')));
        $this->assertSame(30.0, $rates->hourlyRateFor($employee, Carbon::parse('2026-06-01')));
    }

    public function test_project_hours_grid_and_export(): void
    {
        $ctx = $this->projectWithEmployee();
        app(DailyHoursService::class)->upsertCell(
            $ctx['project'],
            $ctx['employee'],
            Carbon::parse('2026-05-05'),
            7.5,
        );

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->get($this->workspaceRoute('projects.show', $ctx['organization'], [
                'project' => $ctx['project'],
                'tab' => 'hours',
                'month' => '2026-05',
            ]))
            ->assertOk()
            ->assertSee('Client rollout')
            ->assertSee('EMP-001');

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->get($this->workspaceRoute('projects.hours.export', $ctx['organization'], [
                'project' => $ctx['project'],
                'month' => '2026-05',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_hourly_payroll_uses_approved_project_hours(): void
    {
        $ctx = $this->projectWithEmployee();
        $entry = app(DailyHoursService::class)->upsertCell(
            $ctx['project'],
            $ctx['employee'],
            Carbon::parse('2026-05-08'),
            10,
        );

        $entry->update([
            'approval_status' => DailyHoursApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $ctx['user']->id,
        ]);

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->post($this->workspaceRoute('payroll.store', $ctx['organization']), [
                'year' => 2026,
                'month' => 5,
                'calculation_mode' => 'hourly',
                'generation_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
            ])
            ->assertRedirect();

        $run = PayrollRun::query()->first();
        $item = $run->items()->first();

        $this->assertNotNull($item);
        $this->assertSame('10.00', (string) $item->hours_worked);
        $this->assertSame('25.00', (string) $item->hourly_rate);
        $this->assertSame('250.00', (string) $item->gross_salary);
    }

    public function test_invoice_from_approved_project_hours(): void
    {
        $ctx = $this->projectWithEmployee();
        $entry = app(DailyHoursService::class)->upsertCell(
            $ctx['project'],
            $ctx['employee'],
            Carbon::parse('2026-05-03'),
            8,
        );

        $entry->update([
            'approval_status' => DailyHoursApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $ctx['user']->id,
        ]);

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->post($this->workspaceRoute('invoices.from-hours', $ctx['organization']), [
                'project_id' => $ctx['project']->id,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
                'client_name' => 'Client SHPK',
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame(InvoiceSource::Hours, $invoice->source);
        $this->assertSame('200.00', (string) $invoice->amount);
        $this->assertSame($ctx['project']->id, $invoice->project_id);
    }

    public function test_project_document_upload_is_scoped_to_project(): void
    {
        Storage::fake('local');
        $ctx = $this->projectWithEmployee();

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->post($this->workspaceRoute('project-documents.store', $ctx['organization']), [
                'project_id' => $ctx['project']->id,
                'category' => ProjectDocumentCategory::Travel->value,
                'title' => 'Hotel receipt',
                'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->get($this->workspaceRoute('project-documents.index', $ctx['organization'], ['project' => $ctx['project']->id]))
            ->assertOk()
            ->assertSee('Hotel receipt');
    }

    public function test_admin_can_add_custom_nav_category(): void
    {
        $ctx = $this->proWorkspace();

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->post($this->workspaceRoute('nav-items.store', $ctx['organization']), [
                'label' => 'Vendors',
                'url' => 'https://example.com/vendors',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workspace_nav_items', [
            'organization_id' => $ctx['organization']->id,
            'label' => 'Vendors',
        ]);

        $item = WorkspaceNavItem::query()->first();

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->delete($this->workspaceRoute('nav-items.destroy', $ctx['organization'], ['navItem' => $item->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('workspace_nav_items', ['id' => $item->id]);
    }

    public function test_admin_limit_blocks_extra_admin_invitations(): void
    {
        Mail::fake();
        $ctx = $this->proWorkspace();
        $organization = $ctx['organization'];
        $invitations = app(InvitationService::class);

        $adminOne = User::factory()->create(['email' => 'admin1@acme.test']);
        $adminTwo = User::factory()->create(['email' => 'admin2@acme.test']);
        $organization->users()->attach($adminOne->id, [
            'role' => OrganizationRole::Admin->value,
            'joined_at' => now(),
        ]);
        $organization->users()->attach($adminTwo->id, [
            'role' => OrganizationRole::Admin->value,
            'joined_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $invitations->send(
            $organization,
            $ctx['user'],
            'admin3@acme.test',
            OrganizationRole::Admin,
        );
    }

    public function test_dashboard_shows_phase1_hour_kpis(): void
    {
        $ctx = $this->projectWithEmployee();
        app(DailyHoursService::class)->upsertCell(
            $ctx['project'],
            $ctx['employee'],
            Carbon::parse('2026-05-01'),
            5,
        );

        $this->actingAs($ctx['user'])
            ->withSession(['current_organization_id' => $ctx['organization']->id])
            ->get($this->workspaceRoute('dashboard', $ctx['organization']))
            ->assertOk()
            ->assertSee(__('dashboard.active_projects'))
            ->assertSee(__('dashboard.hours_this_month'))
            ->assertSee(__('dashboard.pending_hours_approvals'));
    }
}
