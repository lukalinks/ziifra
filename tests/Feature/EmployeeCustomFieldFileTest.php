<?php

namespace Tests\Feature;

use App\Enums\CustomFieldType;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;
use App\Support\EmployeeCustomFieldFile;
use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class EmployeeCustomFieldFileTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_owner_can_upload_file_custom_field_when_creating_employee(): void
    {
        Storage::fake('local');

        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.store', $result['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'new_custom_fields' => [
                    [
                        'name' => 'Signed contract',
                        'type' => 'file',
                        'file' => $file,
                    ],
                ],
            ])
            ->assertRedirect($this->workspaceRoute('employees.index', $result['organization']));

        $employee = Employee::query()->where('first_name', 'Jane')->first();
        $definition = EmployeeFieldDefinition::query()->where('name', 'Signed contract')->first();
        $value = $employee->fieldValues()->where('employee_field_definition_id', $definition->id)->first();

        $this->assertNotNull($value);
        $meta = EmployeeCustomFieldFile::decode($value->value);
        $this->assertNotNull($meta);
        Storage::disk('local')->assertExists($meta['path']);
    }

    public function test_hr_cannot_define_inline_file_custom_field_when_creating_employee(): void
    {
        Storage::fake('local');

        $data = $this->createCompanyA();
        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->post($this->workspaceRoute('employees.store', $data['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'new_custom_fields' => [
                    [
                        'name' => 'Signed contract',
                        'type' => 'file',
                        'file' => $file,
                    ],
                ],
            ])
            ->assertSessionHasErrors('new_custom_fields');

        $this->assertDatabaseMissing('employee_field_definitions', [
            'organization_id' => $data['organization']->id,
            'name' => 'Signed contract',
        ]);
    }

    public function test_employee_custom_field_file_can_be_downloaded(): void
    {
        Storage::fake('local');

        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        CurrentOrganization::set($result['organization']);
        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);
        $definition = EmployeeFieldDefinition::query()->create([
            'organization_id' => $result['organization']->id,
            'name' => 'ID Scan',
            'key' => 'id_scan',
            'type' => CustomFieldType::File,
            'sort_order' => 1,
        ]);

        $path = sprintf('organizations/%d/employees/%d/custom-fields/%d/test.pdf', $result['organization']->id, $employee->id, $definition->id);
        Storage::disk('local')->put($path, 'pdf-content');

        $employee->fieldValues()->create([
            'employee_field_definition_id' => $definition->id,
            'value' => EmployeeCustomFieldFile::encode($path, 'id-scan.pdf'),
        ]);
        CurrentOrganization::clear();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.custom-fields.download', $result['organization'], [
                'employee' => $employee,
                'fieldDefinition' => $definition,
            ]))
            ->assertOk()
            ->assertDownload('id-scan.pdf');
    }
}
