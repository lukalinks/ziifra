<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\LeaveDayCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(
        protected LeaveBalanceService $balances,
        protected LeaveNotificationService $notifications,
        protected EmployeeProfileService $profiles,
    ) {}

    /**
     * @param  array{employee_id: int, leave_type_id: int, start_date: string, end_date: string, notes?: string|null}  $data
     */
    public function create(array $data, User $submittedBy): LeaveRequest
    {
        $organization = CurrentOrganization::check();

        $employee = $this->resolveEmployee($data, $submittedBy, $organization);
        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);

        $days = LeaveDayCalculator::countDays($organization, $data['start_date'], $data['end_date']);

        if ($days <= 0) {
            throw ValidationException::withMessages([
                'end_date' => 'The selected range has no working days based on your company work week.',
            ]);
        }

        $leaveRequest = LeaveRequest::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'submitted_by_user_id' => $submittedBy->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $days,
            'status' => LeaveRequestStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->notifications->notifySubmitted($leaveRequest->load(['employee', 'leaveType', 'submittedBy', 'organization']));

        return $leaveRequest;
    }

    /**
     * @param  array{employee_id?: int, leave_type_id: int, start_date: string, end_date: string, notes?: string|null}  $data
     */
    protected function resolveEmployee(array $data, User $submittedBy, Organization $organization): Employee
    {
        $role = $submittedBy->roleIn($organization);

        if ($role?->canRequestOwnLeave() && ! ($role->canManageLeave())) {
            $employee = $this->profiles->employeeFor($submittedBy, $organization);

            if ($employee === null) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Your account is not linked to an employee profile. Contact HR.',
                ]);
            }

            return $employee;
        }

        return Employee::query()->findOrFail($data['employee_id'] ?? 0);
    }

    public function approve(LeaveRequest $request, User $reviewer): LeaveRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be approved.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer) {
            $balance = $this->balances->balanceFor(
                $request->employee,
                $request->leaveType,
                (int) $request->start_date->year,
            );

            if (! $this->balances->hasAvailableDays($balance, (float) $request->days)) {
                throw ValidationException::withMessages([
                    'days' => sprintf(
                        'Insufficient balance. %.1f days remaining for %s in %d.',
                        $balance->remainingDays(),
                        $request->leaveType->name,
                        $balance->year,
                    ),
                ]);
            }

            $this->balances->deduct($balance, (float) $request->days);

            $request->update([
                'status' => LeaveRequestStatus::Approved,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $fresh = $request->fresh(['employee', 'leaveType', 'submittedBy', 'reviewedBy', 'organization']);
            $this->notifications->notifyReviewed($fresh, $reviewer);

            return $fresh;
        });
    }

    public function reject(LeaveRequest $request, User $reviewer, ?string $reason = null): LeaveRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be rejected.',
            ]);
        }

        $request->update([
            'status' => LeaveRequestStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $fresh = $request->fresh(['employee', 'leaveType', 'submittedBy', 'reviewedBy', 'organization']);
        $this->notifications->notifyReviewed($fresh, $reviewer);

        return $fresh;
    }

    public function cancel(LeaveRequest $request): LeaveRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be cancelled.',
            ]);
        }

        $request->update([
            'status' => LeaveRequestStatus::Cancelled,
        ]);

        return $request->fresh(['employee', 'leaveType', 'submittedBy', 'reviewedBy']);
    }

    public static function seedDefaultTypes(Organization $organization): void
    {
        if ($organization->leaveTypes()->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Annual leave', 'default_days_per_year' => 20, 'is_paid' => true, 'sort_order' => 1],
            ['name' => 'Sick leave', 'default_days_per_year' => 10, 'is_paid' => true, 'sort_order' => 2],
        ];

        foreach ($defaults as $type) {
            $organization->leaveTypes()->create($type);
        }
    }
}
