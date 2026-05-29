<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Services\PayrollTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class PayrollTimeGridTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    public function test_search_matches_first_name_case_insensitively(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Besnik',
            'last_name' => 'Beqiri',
            'employee_code' => 'EMP-099',
        ]);

        $service = app(PayrollTimeService::class);

        $byFirst = $service->grid($organization, now()->year, now()->month, null, 'besnik');
        $byLast = $service->grid($organization, now()->year, now()->month, null, 'beqiri');

        $this->assertCount(1, $byFirst['rows']);
        $this->assertSame('Besnik', $byFirst['rows'][0]['employee']->first_name);
        $this->assertCount(1, $byLast['rows']);
    }

    public function test_search_matches_full_name_in_last_name_field(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => '',
            'last_name' => 'Besnik Beqiri',
            'employee_code' => 'EMP-100',
        ]);

        $service = app(PayrollTimeService::class);
        $grid = $service->grid($organization, now()->year, now()->month, null, 'Besnik');

        $this->assertCount(1, $grid['rows']);
        $this->assertSame('Besnik Beqiri', $grid['rows'][0]['employee']->last_name);
    }

    public function test_hours_editable_when_employee_has_single_project_and_all_projects_selected(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Solo Site',
            'status' => 'active',
        ]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Solo',
            'last_name' => 'Worker',
        ]);
        $employee->projects()->attach($project->id);

        $service = app(PayrollTimeService::class);
        $grid = $service->grid($organization, now()->year, now()->month, null, null);

        $row = collect($grid['rows'])->firstWhere(fn (array $r) => $r['employee']->id === $employee->id);

        $this->assertNotNull($row);
        $this->assertTrue($row['hours_editable']);
        $this->assertSame($project->id, $row['hours_project_id']);
        $this->assertTrue($grid['any_hours_editable']);
    }

    public function test_payroll_time_defaults_to_first_project(): void
    {
        $demo = $this->seedDemoOrganization();
        $this->actingAsOwner($demo);

        $first = Project::query()->orderBy('name')->first();
        $this->assertNotNull($first);

        $response = $this->get(route('payroll-time.index', $demo['organization']));

        $response->assertOk();
        $response->assertViewHas('grid', fn (array $grid) => $grid['project']?->id === $first->id);
    }
}
