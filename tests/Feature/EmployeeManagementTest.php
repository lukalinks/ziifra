<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_employee_in_own_organization(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.store', $result['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@acme.test',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
            ])
            ->assertRedirect($this->workspaceRoute('employees.index', $result['organization']));

        $this->assertDatabaseHas('employees', [
            'organization_id' => $result['organization']->id,
            'email' => 'jane@acme.test',
        ]);
    }

    public function test_index_filters_employees_by_project(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $onProject = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'On',
            'last_name' => 'Project',
        ]);
        Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Off',
            'last_name' => 'Project',
        ]);

        $project = \App\Models\Project::query()->create([
            'organization_id' => $result['organization']->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Website',
            'status' => \App\Enums\ProjectStatus::Active->value,
        ]);
        $project->members()->attach($onProject->id);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.index', $result['organization'], ['project_id' => $project->id]))
            ->assertOk()
            ->assertSee('On Project', false)
            ->assertDontSee('Off Project', false);
    }

    public function test_manager_can_list_but_not_create_employees(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        app(InvitationService::class)->send(
            $result['organization'],
            $result['user'],
            'manager@acme.test',
            OrganizationRole::Manager,
        );

        $invitation = $result['organization']->invitations()->first();
        app(InvitationService::class)->accept($invitation, 'Team Manager', 'password123');

        $manager = \App\Models\User::query()->where('email', 'manager@acme.test')->first();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.index', $result['organization']))
            ->assertOk();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.create', $result['organization']))
            ->assertForbidden();
    }

    public function test_employee_role_cannot_access_employees_index(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        app(InvitationService::class)->send(
            $result['organization'],
            $result['user'],
            'staff@acme.test',
            OrganizationRole::Employee,
        );

        $invitation = $result['organization']->invitations()->first();
        app(InvitationService::class)->accept($invitation, 'Staff Member', 'password123');

        $staff = \App\Models\User::query()->where('email', 'staff@acme.test')->first();

        $this->actingAs($staff)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_employee_profile_url_uses_employee_code(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'employee_code' => 'EMP-042',
        ]);

        $codeUrl = $this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]);

        $this->assertStringContainsString('/employees/EMP-042', $codeUrl);
        $this->assertStringNotContainsString('/employees/'.$employee->id, $codeUrl);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($codeUrl)
            ->assertOk()
            ->assertSee('Jane Doe', false);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee->id]))
            ->assertRedirect($codeUrl);
    }

    public function test_default_overview_tab_is_omitted_from_employee_profile_url(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'employee_code' => 'EMP-002',
        ]);

        $cleanUrl = $this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($cleanUrl.'?tab=overview')
            ->assertRedirect($cleanUrl);
    }

    public function test_new_employees_receive_auto_generated_code(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Auto',
            'last_name' => 'Code',
        ]);

        $this->assertNotNull($employee->employee_code);
        $this->assertMatchesRegularExpression('/^EMP-\d+$/', $employee->employee_code);
    }
}
