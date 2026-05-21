<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TimeEntryService
{
    public function __construct(
        protected TimeAuthorizationService $timeAuth,
        protected EmployeeProfileService $profiles,
    ) {}

    public function clockIn(User $user, ?int $employeeId = null): TimeEntry
    {
        $organization = CurrentOrganization::check();
        $employee = $this->resolveEmployee($user, $organization, $employeeId);

        if (! $this->timeAuth->canClockFor($user, $organization, $employee)) {
            throw ValidationException::withMessages([
                'employee' => 'You cannot clock in for this employee.',
            ]);
        }

        $open = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'clock' => 'An open time entry already exists. Clock out first.',
            ]);
        }

        return TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'recorded_by_user_id' => $user->id,
            'clock_in' => now(),
        ]);
    }

    public function clockOut(User $user, ?int $employeeId = null, ?int $breakMinutes = null, ?string $notes = null): TimeEntry
    {
        $organization = CurrentOrganization::check();
        $employee = $this->resolveEmployee($user, $organization, $employeeId);

        $entry = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        if ($entry === null) {
            throw ValidationException::withMessages([
                'clock' => 'No open time entry to clock out.',
            ]);
        }

        if (! $this->timeAuth->canClockFor($user, $organization, $employee)) {
            throw ValidationException::withMessages([
                'clock' => 'You cannot clock out for this employee.',
            ]);
        }

        $payload = [
            'clock_out' => now(),
        ];

        if ($breakMinutes !== null) {
            $payload['break_minutes'] = max(0, $breakMinutes);
        }

        if ($notes !== null) {
            $payload['notes'] = $notes !== '' ? $notes : null;
        }

        $entry->update($payload);

        return $entry->fresh(['employee']);
    }

    /**
     * @param  array{
     *     employee_id: int,
     *     clock_in: string,
     *     clock_out?: string|null,
     *     break_minutes?: int,
     *     notes?: string|null
     * }  $data
     */
    public function storeManual(User $user, array $data): TimeEntry
    {
        $organization = CurrentOrganization::check();

        if (! $this->timeAuth->canManageEntries($user, $organization)) {
            throw ValidationException::withMessages([
                'entry' => 'You cannot create time entries.',
            ]);
        }

        $employee = Employee::query()->findOrFail($data['employee_id']);
        $timezone = $organization->timezone ?? config('app.timezone');
        $clockIn = Carbon::parse($data['clock_in'], $timezone);
        $clockOut = filled($data['clock_out'] ?? null)
            ? Carbon::parse($data['clock_out'], $timezone)
            : null;

        if ($clockOut !== null && $clockOut->lte($clockIn)) {
            throw ValidationException::withMessages([
                'clock_out' => 'Clock out must be after clock in.',
            ]);
        }

        if ($clockOut === null && $this->openEntryFor($employee) !== null) {
            throw ValidationException::withMessages([
                'clock_out' => 'This employee already has an open time entry.',
            ]);
        }

        return TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'recorded_by_user_id' => $user->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_minutes' => max(0, (int) ($data['break_minutes'] ?? 0)),
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
        ]);
    }

    /**
     * @param  array{
     *     clock_in: string,
     *     clock_out?: string|null,
     *     break_minutes?: int,
     *     notes?: string|null
     * }  $data
     */
    public function updateEntry(User $user, TimeEntry $entry, array $data): TimeEntry
    {
        $organization = CurrentOrganization::check();

        if (! $this->timeAuth->canManageEntries($user, $organization)) {
            throw ValidationException::withMessages([
                'entry' => 'You cannot update time entries.',
            ]);
        }

        $timezone = $organization->timezone ?? config('app.timezone');
        $clockIn = Carbon::parse($data['clock_in'], $timezone);
        $clockOut = filled($data['clock_out'] ?? null)
            ? Carbon::parse($data['clock_out'], $timezone)
            : null;

        if ($clockOut !== null && $clockOut->lte($clockIn)) {
            throw ValidationException::withMessages([
                'clock_out' => 'Clock out must be after clock in.',
            ]);
        }

        if ($clockOut === null) {
            $existingOpen = TimeEntry::query()
                ->where('employee_id', $entry->employee_id)
                ->whereNull('clock_out')
                ->where('id', '!=', $entry->id)
                ->exists();

            if ($existingOpen) {
                throw ValidationException::withMessages([
                    'clock_out' => 'This employee already has an open time entry.',
                ]);
            }
        }

        $entry->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_minutes' => max(0, (int) ($data['break_minutes'] ?? 0)),
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
        ]);

        return $entry->fresh(['employee', 'recordedBy']);
    }

    public function deleteEntry(User $user, TimeEntry $entry): void
    {
        $organization = CurrentOrganization::check();

        if (! $this->timeAuth->canManageEntries($user, $organization)) {
            throw ValidationException::withMessages([
                'entry' => 'You cannot delete time entries.',
            ]);
        }

        $entry->delete();
    }

    public function openEntryFor(Employee $employee): ?TimeEntry
    {
        return TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();
    }

    /**
     * @return array{total_minutes: int, overtime_minutes: int}
     */
    public function dailyTotals(Employee $employee, string $date, int $standardMinutes = 480): array
    {
        $entries = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('clock_in', $date)
            ->whereNotNull('clock_out')
            ->get();

        $total = $entries->sum(fn (TimeEntry $e) => $e->workedMinutes() ?? 0);

        return [
            'total_minutes' => $total,
            'overtime_minutes' => max(0, $total - $standardMinutes),
        ];
    }

    protected function resolveEmployee(User $user, Organization $organization, ?int $employeeId): Employee
    {
        if ($this->timeAuth->canViewAll($user, $organization)) {
            if ($employeeId === null) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Select an employee to clock in.',
                ]);
            }

            return Employee::query()->findOrFail($employeeId);
        }

        if ($employeeId !== null) {
            $employee = Employee::query()->findOrFail($employeeId);

            if (! $this->timeAuth->canClockFor($user, $organization, $employee)) {
                throw ValidationException::withMessages([
                    'employee_id' => 'You cannot clock for this employee.',
                ]);
            }

            return $employee;
        }

        $employee = $this->profiles->employeeFor($user, $organization);

        if ($employee === null) {
            throw ValidationException::withMessages([
                'employee_id' => 'Your account is not linked to an employee profile.',
            ]);
        }

        return $employee;
    }
}
