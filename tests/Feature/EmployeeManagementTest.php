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
}
