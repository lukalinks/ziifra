<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\ChatMessage;
use App\Models\Employee;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_post_and_view_chat(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('chat.store', $result['organization']), [
                'body' => 'Hello team!',
            ])
            ->assertRedirect();

        $message = ChatMessage::query()->first();
        $this->assertNotNull($message);
        $this->assertSame('Hello team!', $message->body);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('chat.index', $result['organization']))
            ->assertOk()
            ->assertSee('Hello team!');
    }

    public function test_user_can_delete_own_message(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $message = ChatMessage::query()->create([
            'organization_id' => $result['organization']->id,
            'user_id' => $result['user']->id,
            'body' => 'Temporary note',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('chat.destroy', $result['organization'], ['chatMessage' => $message]))
            ->assertRedirect();

        $this->assertNull($message->fresh());
    }

    public function test_employee_cannot_delete_other_users_message(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $message = ChatMessage::query()->create([
            'organization_id' => $result['organization']->id,
            'user_id' => $result['user']->id,
            'body' => 'Owner message',
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('chat.destroy', $result['organization'], ['chatMessage' => $message]))
            ->assertForbidden();
    }

    public function test_users_can_exchange_private_messages(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['name' => 'Era Staff']);
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('chat.store', $result['organization']), [
                'body' => 'Hi Era, private note',
                'recipient_user_id' => $employeeUser->id,
            ])
            ->assertRedirect();

        $dm = ChatMessage::query()->first();
        $this->assertSame($employeeUser->id, $dm->recipient_user_id);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('chat.index', $result['organization'], ['with' => $result['user']->id]))
            ->assertOk()
            ->assertSee('Hi Era, private note');

        $outsider = User::factory()->create();
        $result['organization']->users()->attach($outsider->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);
        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $outsider->id,
        ]);

        $this->actingAs($outsider)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('chat.index', $result['organization']))
            ->assertOk()
            ->assertDontSee('Hi Era, private note');
    }
}
