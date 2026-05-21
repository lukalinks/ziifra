<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_approved_leave_on_calendar(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        LeaveRequest::factory()
            ->forEmployee($employee, $leaveType, $owner['user'])
            ->create([
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'days' => 3,
                'status' => LeaveRequestStatus::Approved,
            ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.calendar', $owner['organization'], [
                'year' => 2026,
                'month' => 6,
            ]))
            ->assertOk()
            ->assertSee('Alice Smith', false)
            ->assertSee('June 2026', false);
    }

    public function test_employee_calendar_hides_other_peoples_leave(): void
    {
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

        $other = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Bob',
            'last_name' => 'Other',
        ]);

        $owner['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        LeaveRequest::factory()
            ->forEmployee($other, $leaveType, $owner['user'])
            ->create([
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-10',
                'days' => 1,
                'status' => LeaveRequestStatus::Approved,
            ]);

        LeaveRequest::factory()
            ->forEmployee($employeeRecord, $leaveType, $employeeUser)
            ->create([
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-15',
                'days' => 1,
                'status' => LeaveRequestStatus::Approved,
            ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.calendar', $owner['organization'], [
                'year' => 2026,
                'month' => 6,
            ]))
            ->assertOk()
            ->assertSee('Jane Doe', false)
            ->assertDontSee('Bob Other', false);
    }

    public function test_calendar_shows_kosovo_holiday_when_enabled(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $owner['organization']->update(['observe_kosovo_holidays' => true]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.calendar', $owner['organization'], [
                'year' => 2026,
                'month' => 2,
            ]))
            ->assertOk()
            ->assertSee('Independence Day', false);
    }

    public function test_pending_leave_hidden_when_toggle_off(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($owner['organization'])->create([
            'first_name' => 'Pending',
            'last_name' => 'Person',
        ]);

        $leaveType = LeaveType::query()->where('organization_id', $owner['organization']->id)->first();

        LeaveRequest::factory()
            ->forEmployee($employee, $leaveType, $owner['user'])
            ->create([
                'start_date' => '2026-06-20',
                'end_date' => '2026-06-20',
                'days' => 1,
                'status' => LeaveRequestStatus::Pending,
            ]);

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('leave.calendar', $owner['organization'], [
                'year' => 2026,
                'month' => 6,
                'pending' => 0,
            ]))
            ->assertOk()
            ->assertDontSee('Pending Person', false);
    }
}
