<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\PayrollTimeExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class PayrollTimeFiltersTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    public function test_index_shows_separate_year_and_all_year_month_filters(): void
    {
        $demo = $this->seedDemoOrganization();
        $this->actingAsOwner($demo);

        $response = $this->get(route('payroll-time.index', [
            'organization' => $demo['organization'],
            'year' => now()->year,
            'month' => 'all',
        ]));

        $response->assertOk();
        $response->assertViewHas('monthAll', true);
        $response->assertSee('id="pt-year-select"', false);
        $response->assertSee('value="all"', false);
        $response->assertSee(__('payroll_time.all_months'), false);
    }

    public function test_year_pdf_export_respects_employee_search_filter(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'UniqueYear',
            'last_name' => 'ExportTest',
            'employee_code' => 'EMP-YR-01',
        ]);

        $all = app(PayrollTimeExportService::class)->exportData(
            $organization,
            now()->year,
            null,
            null,
            null,
            null,
        );

        $filtered = app(PayrollTimeExportService::class)->exportData(
            $organization,
            now()->year,
            null,
            null,
            null,
            'UniqueYear',
        );

        $this->assertGreaterThanOrEqual(1, count($all['rows']));
        $this->assertCount(1, $filtered['rows']);
        $this->assertSame('UniqueYear', $filtered['rows'][0]['employee']->first_name);
    }

    public function test_export_pdf_route_accepts_month_all_for_full_year(): void
    {
        $demo = $this->seedDemoOrganization();
        $this->actingAsOwner($demo);

        $response = $this->get(route('payroll-time.export.pdf', [
            'organization' => $demo['organization'],
            'year' => now()->year,
            'month' => 'all',
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
    }
}
