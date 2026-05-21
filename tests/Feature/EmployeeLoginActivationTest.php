<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\Employee;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeLoginActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_activate_login_for_employee(): void
    {
        Mail::fake();

        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => null,
        ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('employees.activate-login', $owner['organization'], ['employee' => $employee]))
            ->assertRedirect($this->workspaceRoute('employees.show', $owner['organization'], ['employee' => $employee]));

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $owner['organization']->id,
            'email' => 'jane@acme.test',
        ]);

        Mail::assertSent(TeamInvitationMail::class, function (TeamInvitationMail $mail) {
            return $mail->hasTo('jane@acme.test');
        });
    }

    public function test_activate_login_links_existing_workspace_member(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $member = User::factory()->create(['email' => 'jane@acme.test']);
        $owner['organization']->users()->attach($member->id, [
            'role' => 'employee',
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => null,
        ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('employees.activate-login', $owner['organization'], ['employee' => $employee]))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => $member->id,
        ]);

        $this->assertDatabaseMissing('invitations', [
            'organization_id' => $owner['organization']->id,
            'email' => 'jane@acme.test',
            'accepted_at' => null,
        ]);
    }

    public function test_accepting_invitation_from_activate_login_links_employee(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'newhire@acme.test',
        ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('employees.activate-login', $owner['organization'], ['employee' => $employee]));

        $invitation = Invitation::query()
            ->where('organization_id', $owner['organization']->id)
            ->where('email', 'newhire@acme.test')
            ->first();

        $this->assertNotNull($invitation);

        app(InvitationService::class)->accept($invitation, 'New Hire', 'password123');

        $user = User::query()->where('email', 'newhire@acme.test')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_employees_index_filters_missing_login(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $withoutLogin = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'no-login@acme.test',
            'user_id' => null,
        ]);

        $withLogin = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'has-login@acme.test',
            'user_id' => $owner['user']->id,
        ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('employees.index', $owner['organization'], ['missing_login' => 1]))
            ->assertOk()
            ->assertSee($withoutLogin->fullName(), false)
            ->assertDontSee($withLogin->fullName(), false);
    }

    public function test_employee_show_displays_activate_login_action(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => null,
        ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('employees.show', $owner['organization'], ['employee' => $employee]))
            ->assertOk()
            ->assertSee(__('employees.activate_login'), false);
    }
}
