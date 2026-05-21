<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Mail\LeaveRequestReviewedMail;
use App\Mail\LeaveRequestSubmittedMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeLeaveSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_own_leave(): void
    {
        Mail::fake();

        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        $employeeRecord = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $leaveType = LeaveType::query()
            ->where('organization_id', $owner['organization']->id)
            ->first();

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.store', $owner['organization']), [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
            ])
            ->assertRedirect();

        $request = LeaveRequest::query()->where('employee_id', $employeeRecord->id)->first();
        $this->assertNotNull($request);
        $this->assertSame(LeaveRequestStatus::Pending, $request->status);

        Mail::assertQueued(LeaveRequestSubmittedMail::class);
    }

    public function test_employee_only_sees_own_leave_requests(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create(['email' => 'jane@acme.test']);
        $employeeRecord = Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'jane@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $otherEmployee = Employee::factory()->forOrganization($owner['organization'])->create();

        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        LeaveRequest::factory()
            ->forEmployee($otherEmployee, $leaveType, $owner['user'])
            ->create([
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-02',
                'days' => 2,
            ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.index', $owner['organization']))
            ->assertOk()
            ->assertDontSee($otherEmployee->fullName(), false);
    }

    public function test_invitation_links_employee_by_email(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        Employee::factory()->forOrganization($owner['organization'])->create([
            'email' => 'newhire@acme.test',
        ]);

        $invitation = app(InvitationService::class)->send(
            $owner['organization'],
            $owner['user'],
            'newhire@acme.test',
            OrganizationRole::Employee,
        );

        app(InvitationService::class)->accept($invitation, 'New Hire', 'password123');

        $user = User::query()->where('email', 'newhire@acme.test')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('employees', [
            'organization_id' => $owner['organization']->id,
            'email' => 'newhire@acme.test',
            'user_id' => $user->id,
        ]);
    }

    public function test_approve_sends_email_to_employee(): void
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
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.store', $owner['organization']), [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
            ]);

        $request = LeaveRequest::query()->where('employee_id', $employee->id)->first();

        Mail::fake();

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->post($this->workspaceRoute('leave.approve', $owner['organization'], ['leaveRequest' => $request]));

        Mail::assertQueued(LeaveRequestReviewedMail::class, function (LeaveRequestReviewedMail $mail) {
            return $mail->hasTo('jane@acme.test');
        });
    }
}
