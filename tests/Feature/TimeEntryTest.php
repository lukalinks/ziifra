<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_clock_in_and_out(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create(['email' => 'staff@acme.test']);
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'email' => 'staff@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('time.clock-in', $result['organization']))
            ->assertRedirect();

        $entry = TimeEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertNull($entry->clock_out);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('time.clock-out', $result['organization']), [
                'break_minutes' => 30,
                'notes' => 'Lunch break',
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertNotNull($entry->clock_out);
        $this->assertSame(30, $entry->break_minutes);
        $this->assertSame('Lunch break', $entry->notes);
    }

    public function test_hr_clocks_in_for_employee(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('time.clock-in', $result['organization']), [
                'employee_id' => $employee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'employee_id' => $employee->id,
            'recorded_by_user_id' => $result['user']->id,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('time.index', $result['organization']))
            ->assertOk()
            ->assertSee($employee->fullName())
            ->assertSee(__('time.timesheet'));
    }

    public function test_employee_cannot_clock_in_twice_without_clock_out(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $session = ['current_organization_id' => $result['organization']->id];

        $this->actingAs($employeeUser)
            ->withSession($session)
            ->post($this->workspaceRoute('time.clock-in', $result['organization']))
            ->assertRedirect();

        $this->actingAs($employeeUser)
            ->withSession($session)
            ->post($this->workspaceRoute('time.clock-in', $result['organization']))
            ->assertSessionHasErrors('clock');
    }

    public function test_hr_can_create_update_and_delete_manual_entry(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($result['organization'])->create();
        $session = ['current_organization_id' => $result['organization']->id];

        $clockIn = Carbon::now()->startOfWeek()->setTime(9, 0)->format('Y-m-d\TH:i');
        $clockOut = Carbon::now()->startOfWeek()->setTime(17, 0)->format('Y-m-d\TH:i');

        $this->actingAs($result['user'])
            ->withSession($session)
            ->post($this->workspaceRoute('time.entries.store', $result['organization']), [
                'employee_id' => $employee->id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_minutes' => 45,
                'notes' => 'Manual correction',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $entry = TimeEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame(45, $entry->break_minutes);
        $this->assertSame('Manual correction', $entry->notes);

        $updatedIn = Carbon::now()->startOfWeek()->setTime(8, 30)->format('Y-m-d\TH:i');

        $this->actingAs($result['user'])
            ->withSession($session)
            ->put($this->workspaceRoute('time.entries.update', $result['organization'], ['timeEntry' => $entry->id]), [
                'clock_in' => $updatedIn,
                'clock_out' => $clockOut,
                'break_minutes' => 30,
                'notes' => 'Updated note',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $entry->refresh();
        $this->assertSame(30, $entry->break_minutes);
        $this->assertSame('Updated note', $entry->notes);

        $this->actingAs($result['user'])
            ->withSession($session)
            ->delete($this->workspaceRoute('time.entries.destroy', $result['organization'], ['timeEntry' => $entry->id]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_hr_can_export_timesheet_csv(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        TimeEntry::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'recorded_by_user_id' => $result['user']->id,
            'clock_in' => Carbon::now()->startOfWeek()->setTime(9, 0),
            'clock_out' => Carbon::now()->startOfWeek()->setTime(17, 0),
            'break_minutes' => 30,
            'notes' => 'Office day',
        ]);

        $response = $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('time.export', $result['organization']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Jane Doe', $response->getContent());
        $this->assertStringContainsString('Office day', $response->getContent());
        $this->assertStringContainsString('Summary', $response->getContent());
    }

    public function test_employee_cannot_create_manual_entries(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('time.create', $result['organization']))
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('time.entries.store', $result['organization']), [
                'employee_id' => $employee->id,
                'clock_in' => now()->format('Y-m-d\TH:i'),
                'clock_out' => now()->addHours(8)->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }
}
