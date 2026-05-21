<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_team_member(): void
    {
        Mail::fake();

        $service = app(RegisterOrganizationService::class);
        $result = $service->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('team.invitations.store', $result['organization']), [
                'email' => 'hr@acme.test',
                'role' => 'hr',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'email' => 'hr@acme.test',
            'organization_id' => $result['organization']->id,
        ]);

        Mail::assertSent(\App\Mail\TeamInvitationMail::class);
    }

    public function test_invitee_can_accept_and_join_organization(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $invitation = app(InvitationService::class)->send(
            $result['organization'],
            $result['user'],
            'newhire@acme.test',
            OrganizationRole::Employee,
        );

        $response = $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'New Hire',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect($this->workspaceRoute('dashboard', $result['organization']));

        $user = User::query()->where('email', 'newhire@acme.test')->first();
        $this->assertTrue($user->belongsToOrganization($result['organization']));
    }

    public function test_employee_cannot_send_invitations(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        $invite = app(InvitationService::class)->send(
            $result['organization'],
            $result['user'],
            'employee@acme.test',
            OrganizationRole::Employee,
        );

        app(InvitationService::class)->accept($invite, 'Employee User', 'password123');

        $employee = User::query()->where('email', 'employee@acme.test')->first();

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('team.invitations.store', $result['organization']), [
                'email' => 'blocked@acme.test',
                'role' => 'hr',
            ])
            ->assertForbidden();
    }
}
