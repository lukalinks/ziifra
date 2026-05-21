<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_template(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.import.template', $result['organization']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_import_creates_employees_from_csv(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $engineering = Department::query()->create([
            'organization_id' => $result['organization']->id,
            'name' => 'Engineering',
        ]);

        $csv = implode("\n", [
            'first_name,last_name,email,phone,department,position,manager_email,employment_type,employment_status,start_date',
            'Jane,Doe,jane@acme.test,,Engineering,,,full_time,active,2026-03-01',
            'John,Smith,john@acme.test,,,,,full_time,active,',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.import.store', $result['organization']), [
                'file' => $file,
            ])
            ->assertRedirect($this->workspaceRoute('employees.import', $result['organization']))
            ->assertSessionHas('import_result');

        $this->assertDatabaseHas('employees', [
            'organization_id' => $result['organization']->id,
            'email' => 'jane@acme.test',
            'department_id' => $engineering->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'organization_id' => $result['organization']->id,
            'email' => 'john@acme.test',
        ]);
    }

    public function test_import_reports_row_errors(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $csv = "first_name,last_name,email\n,Doe,missing@acme.test\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.import.store', $result['organization']), [
                'file' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('import_result', fn (array $data) => $data['imported'] === 0 && count($data['errors']) === 1);
    }

    public function test_manager_cannot_import(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $manager = \App\Models\User::factory()->create();
        $result['organization']->users()->attach($manager->id, [
            'role' => \App\Enums\OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $csv = "first_name,last_name\nTest,User\n";
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.import.store', $result['organization']), [
                'file' => $file,
            ])
            ->assertForbidden();
    }
}
