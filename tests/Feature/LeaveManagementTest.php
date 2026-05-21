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

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_organization_gets_default_leave_types(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->assertDatabaseHas('leave_types', [
            'organization_id' => $result['organization']->id,
            'name' => 'Annual leave',
        ]);
        $this->assertDatabaseHas('leave_types', [
            'organization_id' => $result['organization']->id,
            'name' => 'Sick leave',
        ]);
    }

    public function test_owner_can_create_and_approve_leave_request(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $leaveType = LeaveType::query()
            ->where('organization_id', $result['organization']->id)
            ->where('name', 'Annual leave')
            ->first();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('leave.store', $result['organization']), [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
                'notes' => 'Summer break',
            ])
            ->assertRedirect();

        $request = LeaveRequest::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($request);
        $this->assertSame(LeaveRequestStatus::Pending, $request->status);
        $this->assertGreaterThan(0, (float) $request->days);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('leave.approve', $result['organization'], ['leaveRequest' => $request]))
            ->assertRedirect($this->workspaceRoute('leave.show', $result['organization'], ['leaveRequest' => $request]));

        $request->refresh();
        $this->assertSame(LeaveRequestStatus::Approved, $request->status);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
        ]);
    }

    public function test_manager_can_view_leave_but_not_create(): void
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
            ->get($this->workspaceRoute('leave.index', $result['organization']))
            ->assertOk();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('leave.create', $result['organization']))
            ->assertForbidden();
    }

    public function test_employee_role_cannot_access_leave(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $staff = User::factory()->create();
        $result['organization']->users()->attach($staff->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($staff)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('leave.index', $result['organization']))
            ->assertForbidden();
    }
}
