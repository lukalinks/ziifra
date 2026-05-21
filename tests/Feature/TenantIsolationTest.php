<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

/**
 * User in Company A cannot access Company B (tenant isolation / IDOR).
 */
class TenantIsolationTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_company_a_user_can_access_own_workspace_dashboard(): void
    {
        $companyA = $this->createCompanyA();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('dashboard', $companyA['organization']))
            ->assertOk();
    }

    public function test_company_a_user_cannot_access_company_b_workspace_dashboard(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('dashboard', $companyB['organization']))
            ->assertForbidden();
    }

    public function test_company_a_user_cannot_use_company_b_session_on_team_page(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->withSession(['current_organization_id' => $companyB['organization']->id])
            ->get($this->workspaceRoute('team.index', $companyB['organization']))
            ->assertForbidden();
    }

    public function test_company_a_user_cannot_view_company_b_employee(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('employees.show', $companyA['organization'], ['employee' => $beta['employee']]))
            ->assertNotFound();
    }

    public function test_company_a_user_cannot_edit_company_b_employee(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('employees.edit', $companyA['organization'], ['employee' => $beta['employee']]))
            ->assertNotFound();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->put($this->workspaceRoute('employees.update', $companyA['organization'], ['employee' => $beta['employee']]), [
                'first_name' => 'Hacked',
                'last_name' => 'User',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('employees', [
            'id' => $beta['employee']->id,
            'first_name' => 'Beta',
        ]);
    }

    public function test_company_a_user_cannot_delete_company_b_employee(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->delete($this->workspaceRoute('employees.destroy', $companyA['organization'], ['employee' => $beta['employee']]))
            ->assertNotFound();

        $this->assertNull($beta['employee']->fresh()->deleted_at);
    }

    public function test_company_a_employee_list_does_not_include_company_b_employees(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('employees.index', $companyA['organization']))
            ->assertOk()
            ->assertDontSee('Beta Secret')
            ->assertDontSee($beta['employee']->email);
    }

    public function test_company_a_user_cannot_delete_company_b_department(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->delete($this->workspaceRoute('settings.departments.destroy', $companyA['organization'], ['department' => $beta['department']]))
            ->assertNotFound();

        $this->assertDatabaseHas('departments', ['id' => $beta['department']->id]);
    }

    public function test_company_a_user_cannot_delete_company_b_position(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->delete($this->workspaceRoute('settings.positions.destroy', $companyA['organization'], ['position' => $beta['position']]))
            ->assertNotFound();

        $this->assertDatabaseHas('positions', ['id' => $beta['position']->id]);
    }

    public function test_company_a_user_cannot_cancel_company_b_invitation(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->delete($this->workspaceRoute('team.invitations.destroy', $companyA['organization'], ['invitation' => $beta['invitation']]))
            ->assertNotFound();

        $this->assertDatabaseHas('invitations', [
            'id' => $beta['invitation']->id,
            'accepted_at' => null,
        ]);
    }

    public function test_outsider_cannot_access_company_a_workspace(): void
    {
        $companyA = $this->createCompanyA();
        $outsider = \App\Models\User::factory()->create(['email' => 'outsider@evil.test']);

        $this->actingAs($outsider)
            ->withSession(['current_organization_id' => $companyA['organization']->id])
            ->get($this->workspaceRoute('dashboard', $companyA['organization']))
            ->assertForbidden();
    }

    public function test_company_a_user_cannot_assign_company_b_manager_to_new_employee(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->post($this->workspaceRoute('employees.store', $companyA['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'manager_id' => $beta['employee']->id,
                'employment_type' => 'full_time',
                'employment_status' => 'active',
            ])
            ->assertSessionHasErrors('manager_id');

        $this->assertDatabaseMissing('employees', [
            'organization_id' => $companyA['organization']->id,
            'first_name' => 'Jane',
        ]);
    }

    public function test_company_b_hr_invitation_stays_in_company_b_when_company_a_owner_tries_to_invite_same_email(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        app(InvitationService::class)->send(
            $companyB['organization'],
            $companyB['user'],
            'shared-hire@test.com',
            OrganizationRole::Hr,
        );

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->post($this->workspaceRoute('team.invitations.store', $companyA['organization']), [
                'email' => 'shared-hire@test.com',
                'role' => 'hr',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'email' => 'shared-hire@test.com',
            'organization_id' => $companyB['organization']->id,
        ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'shared-hire@test.com',
            'organization_id' => $companyA['organization']->id,
        ]);
    }
}
