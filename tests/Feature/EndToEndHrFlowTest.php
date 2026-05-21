<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Mail\LeaveRequestReviewedMail;
use App\Mail\LeaveRequestSubmittedMail;
use App\Mail\TeamInvitationMail;
use App\Mail\WelcomeMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EndToEndHrFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_invite_leave_approve_flow(): void
    {
        Mail::fake();

        $owner = app(RegisterOrganizationService::class)->register(
            'Arben Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        Mail::assertQueued(WelcomeMail::class);

        app(InvitationService::class)->send(
            $owner['organization'],
            $owner['user'],
            'hr@acme.test',
            OrganizationRole::Hr,
        );

        Mail::assertSent(TeamInvitationMail::class);

        $hrUser = User::factory()->create(['email' => 'hr@acme.test']);
        $owner['organization']->users()->attach($hrUser->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.test',
        ]);

        $leaveType = LeaveType::query()
            ->where('organization_id', $owner['organization']->id)
            ->where('name', 'Annual leave')
            ->firstOrFail();

        $this->actingAs($hrUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.store', $owner['organization']), [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
            ])
            ->assertRedirect();

        Mail::assertQueued(LeaveRequestSubmittedMail::class);

        $leaveRequest = LeaveRequest::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($leaveRequest);
        $this->assertSame(LeaveRequestStatus::Pending, $leaveRequest->status);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.approve', $owner['organization'], ['leaveRequest' => $leaveRequest]))
            ->assertRedirect();

        Mail::assertQueued(LeaveRequestReviewedMail::class);

        $leaveRequest->refresh();
        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->status);
    }

    public function test_admin_dashboard_includes_leave_trend_chart(): void
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
            ->assertSee(__('admin_dashboard.leave_trend_title'), false)
            ->assertSee('data-leave-trend-chart', false);
    }
}
