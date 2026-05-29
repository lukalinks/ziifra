<?php

namespace Tests\Feature;

use App\Models\EmployeeDocument;
use App\Services\PayrollTimeArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class PayrollTimeArchiveTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    public function test_archive_month_stores_files_in_payroll_folder(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        app(PayrollTimeArchiveService::class)->archiveMonth(
            $organization,
            now()->year,
            now()->month,
            null,
            $demo['owner'],
        );

        $folder = app(PayrollTimeArchiveService::class)->payrollFolder($organization);

        $this->assertSame('Payroll', $folder->name);
        $this->assertGreaterThanOrEqual(2, EmployeeDocument::query()->where('document_folder_id', $folder->id)->count());
        $this->assertTrue(
            EmployeeDocument::query()->where('document_folder_id', $folder->id)->whereNull('employee_id')->exists()
        );
    }
}
