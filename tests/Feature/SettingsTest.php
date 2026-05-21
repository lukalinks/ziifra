<?php

namespace Tests\Feature;

use App\Enums\CustomFieldType;
use App\Enums\OrganizationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Models\Position;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;
use App\Support\EmployeeCustomFieldFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_settings_hub_lists_available_sections_for_owner(): void
    {
        $data = $this->createCompanyA();

        $this->actingAsCompanyA($data['user'], $data['organization'])
            ->get($this->workspaceRoute('settings.index', $data['organization']))
            ->assertOk()
            ->assertSee('Company')
            ->assertSee('Departments')
            ->assertSee('Positions')
            ->assertSee('Custom fields');
    }

    public function test_hr_can_access_hr_settings_but_not_company(): void
    {
        $data = $this->createCompanyA();
        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.index', $data['organization']))
            ->assertOk()
            ->assertDontSee('Legal details, address')
            ->assertSee('Departments')
            ->assertDontSee(__('settings.employee_fields.title'));

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.company.edit', $data['organization']))
            ->assertForbidden();
    }

    public function test_hr_cannot_manage_custom_field_definitions_via_settings(): void
    {
        $data = $this->createCompanyA();
        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.employee-fields.index', $data['organization']))
            ->assertForbidden();

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->post($this->workspaceRoute('settings.employee-fields.store', $data['organization']), [
                'name' => 'HR injected field',
                'type' => CustomFieldType::Text->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('employee_field_definitions', [
            'organization_id' => $data['organization']->id,
            'name' => 'HR injected field',
        ]);
    }

    public function test_manager_cannot_access_settings(): void
    {
        $data = $this->createCompanyA();
        $manager = User::factory()->create();
        $data['organization']->users()->attach($manager->id, [
            'role' => OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.index', $data['organization']))
            ->assertForbidden();
    }

    public function test_departments_positions_and_custom_fields_crud(): void
    {
        Storage::fake('local');

        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.departments.store', $result['organization']), ['name' => 'Engineering'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.positions.store', $result['organization']), ['title' => 'Software Engineer'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.employee-fields.store', $result['organization']), [
                'name' => 'Contract type',
                'type' => CustomFieldType::Select->value,
                'options' => 'Permanent, Fixed-term',
                'is_required' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('departments', [
            'organization_id' => $result['organization']->id,
            'name' => 'Engineering',
        ]);
        $this->assertDatabaseHas('positions', [
            'organization_id' => $result['organization']->id,
            'title' => 'Software Engineer',
        ]);
        $this->assertDatabaseHas('employee_field_definitions', [
            'organization_id' => $result['organization']->id,
            'name' => 'Contract type',
            'is_required' => true,
        ]);

        $department = Department::query()->where('name', 'Engineering')->first();
        $position = Position::query()->where('title', 'Software Engineer')->first();
        $definition = EmployeeFieldDefinition::query()->where('name', 'Contract type')->first();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.employee-fields.store', $result['organization']), [
                'name' => 'Contract type',
                'type' => CustomFieldType::Text->value,
            ])
            ->assertSessionHasErrors('name');

        CurrentOrganization::set($result['organization']);
        $fileDefinition = EmployeeFieldDefinition::query()->create([
            'organization_id' => $result['organization']->id,
            'name' => 'Signed contract',
            'key' => 'signed_contract',
            'type' => CustomFieldType::File,
            'sort_order' => 2,
        ]);
        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $path = sprintf(
            'organizations/%d/employees/%d/custom-fields/%d/file.pdf',
            $result['organization']->id,
            $employee->id,
            $fileDefinition->id,
        );
        Storage::disk('local')->put($path, 'pdf-content');
        $employee->fieldValues()->create([
            'employee_field_definition_id' => $fileDefinition->id,
            'value' => EmployeeCustomFieldFile::encode($path, 'contract.pdf'),
        ]);
        CurrentOrganization::clear();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('settings.departments.destroy', $result['organization'], ['department' => $department]))
            ->assertRedirect();

        $employee->refresh();
        $this->assertNull($employee->department_id);
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('settings.employee-fields.destroy', $result['organization'], ['fieldDefinition' => $fileDefinition]))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('employee_field_definitions', ['id' => $fileDefinition->id]);
        $this->assertDatabaseMissing('employee_field_values', [
            'employee_field_definition_id' => $fileDefinition->id,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('settings.employee-fields.destroy', $result['organization'], ['fieldDefinition' => $definition]))
            ->assertRedirect();

        $this->assertDatabaseMissing('employee_field_definitions', ['id' => $definition->id]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('settings.positions.destroy', $result['organization'], ['position' => $position]))
            ->assertRedirect();

        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_company_a_cannot_delete_company_b_custom_field_definition(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        CurrentOrganization::set($companyB['organization']);
        $definition = EmployeeFieldDefinition::query()->create([
            'organization_id' => $companyB['organization']->id,
            'name' => 'Secret',
            'key' => 'secret',
            'type' => CustomFieldType::Text,
            'sort_order' => 1,
        ]);
        CurrentOrganization::clear();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->delete($this->workspaceRoute('settings.employee-fields.destroy', $companyA['organization'], ['fieldDefinition' => $definition]))
            ->assertNotFound();

        $this->assertDatabaseHas('employee_field_definitions', ['id' => $definition->id]);
    }

    public function test_duplicate_position_title_in_same_org_fails_validation(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        CurrentOrganization::set($result['organization']);
        Position::query()->create(['title' => 'Analyst']);
        CurrentOrganization::clear();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.positions.store', $result['organization']), ['title' => 'Analyst'])
            ->assertSessionHasErrors('title');
    }
}
