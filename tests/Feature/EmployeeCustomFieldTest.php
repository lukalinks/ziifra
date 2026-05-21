<?php

namespace Tests\Feature;

use App\Enums\CustomFieldType;
use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class EmployeeCustomFieldTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_owner_can_create_employee_with_inline_custom_field(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.store', $result['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'new_custom_fields' => [
                    [
                        'name' => 'Badge ID',
                        'type' => 'text',
                        'value' => 'A-1001',
                    ],
                ],
            ])
            ->assertRedirect($this->workspaceRoute('employees.index', $result['organization']));

        $employee = Employee::query()->where('first_name', 'Jane')->first();
        $this->assertNotNull($employee);

        $this->assertDatabaseHas('employee_field_definitions', [
            'organization_id' => $result['organization']->id,
            'name' => 'Badge ID',
        ]);

        $definition = EmployeeFieldDefinition::query()->where('name', 'Badge ID')->first();
        $this->assertDatabaseHas('employee_field_values', [
            'employee_id' => $employee->id,
            'employee_field_definition_id' => $definition->id,
            'value' => 'A-1001',
        ]);
    }

    public function test_hr_cannot_define_inline_custom_fields_when_creating_employee(): void
    {
        $data = $this->createCompanyA();
        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->post($this->workspaceRoute('employees.store', $data['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'new_custom_fields' => [
                    [
                        'name' => 'Badge ID',
                        'type' => 'text',
                        'value' => 'A-1001',
                    ],
                ],
            ])
            ->assertSessionHasErrors('new_custom_fields');

        $this->assertDatabaseMissing('employee_field_definitions', [
            'organization_id' => $data['organization']->id,
            'name' => 'Badge ID',
        ]);
    }

    public function test_existing_custom_field_values_are_saved_on_update(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        CurrentOrganization::set($result['organization']);
        $definition = EmployeeFieldDefinition::query()->create([
            'organization_id' => $result['organization']->id,
            'name' => 'T-Shirt Size',
            'key' => 't_shirt_size',
            'type' => CustomFieldType::Select,
            'options' => ['S', 'M', 'L'],
            'is_required' => false,
            'sort_order' => 1,
        ]);
        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);
        CurrentOrganization::clear();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->put($this->workspaceRoute('employees.update', $result['organization'], ['employee' => $employee]), [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'custom_fields' => [
                    $definition->id => [
                        'definition_id' => $definition->id,
                        'value' => 'L',
                    ],
                ],
            ])
            ->assertRedirect($this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]));

        $this->assertDatabaseHas('employee_field_values', [
            'employee_id' => $employee->id,
            'employee_field_definition_id' => $definition->id,
            'value' => 'L',
        ]);
    }

    public function test_company_a_cannot_see_company_b_custom_field_definitions_on_form(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        CurrentOrganization::set($companyB['organization']);
        EmployeeFieldDefinition::query()->create([
            'organization_id' => $companyB['organization']->id,
            'name' => 'Secret Code',
            'key' => 'secret_code',
            'type' => CustomFieldType::Text,
            'sort_order' => 1,
        ]);
        CurrentOrganization::clear();

        $this->actingAs($companyA['user'])
            ->withSession(['current_organization_id' => $companyA['organization']->id])
            ->get($this->workspaceRoute('employees.create', $companyA['organization']))
            ->assertOk()
            ->assertDontSee('Secret Code');
    }
}
