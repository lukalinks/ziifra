<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\LeaveRequestReviewedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_header_renders_notification_bell(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee('data-notifications', false)
            ->assertSee(__('notifications.panel_title'), false);
    }

    public function test_hr_receives_in_app_notification_when_employee_submits_leave(): void
    {
        Notification::fake();

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

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.store', $owner['organization']), [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
            ])
            ->assertRedirect();

        Notification::assertSentTo($owner['user'], LeaveRequestSubmittedNotification::class);
    }

    public function test_employee_sees_notification_after_leave_is_approved(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);
        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.store', $owner['organization']), [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
            ]);

        $request = LeaveRequest::query()->where('organization_id', $owner['organization']->id)->first();

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.approve', $owner['organization'], ['leaveRequest' => $request]));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee(__('notifications.leave_reviewed_title'), false);
    }

    public function test_super_admin_header_renders_notification_bell(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@ziifra.test',
            'is_super_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-notifications', false)
            ->assertSee('ziifra-notifications-admin', false);
    }

    public function test_mark_all_read_clears_unread_badge(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $owner['user']->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test.notification',
            'data' => [
                'title' => 'Test alert',
                'body' => 'Something happened',
                'url' => null,
                'organization_id' => $owner['organization']->id,
            ],
        ]);

        $this->assertSame(1, $owner['user']->fresh()->unreadNotifications()->count());

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $owner['user']->fresh()->unreadNotifications()->count());
    }
}
