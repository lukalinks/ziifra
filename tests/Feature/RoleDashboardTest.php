<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_admin_dashboard(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee(__('admin_dashboard.header'), false)
            ->assertSee(__('admin_dashboard.pending_approvals'), false);
    }

    public function test_manager_sees_team_dashboard(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $manager = User::factory()->create();
        $result['organization']->users()->attach($manager->id, [
            'role' => OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('dashboard', $result['organization']))
            ->assertOk()
            ->assertSee(__('team_dashboard.header'), false)
            ->assertDontSee(__('admin_dashboard.pending_approvals'), false);
    }

    public function test_employee_sees_personal_dashboard(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee(__('employee_dashboard.header'), false)
            ->assertSee(__('employee_dashboard.my_requests'), false)
            ->assertSee('ziifra-dashboard-employee', false)
            ->assertSee('ziifra-portal-employee', false)
            ->assertDontSee(__('admin_dashboard.header'), false);
    }
}
