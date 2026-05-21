<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;

class LeaveBalanceService
{
    public function balanceFor(Employee $employee, LeaveType $leaveType, ?int $year = null): LeaveBalance
    {
        $year ??= (int) now()->year;

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($balance !== null) {
            return $balance;
        }

        return LeaveBalance::query()->create([
            'organization_id' => $employee->organization_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
            'entitled_days' => $leaveType->default_days_per_year,
            'used_days' => 0,
        ]);
    }

    public function hasAvailableDays(LeaveBalance $balance, float $days): bool
    {
        return $balance->remainingDays() >= $days;
    }

    public function deduct(LeaveBalance $balance, float $days): void
    {
        $balance->update([
            'used_days' => (float) $balance->used_days + $days,
        ]);
    }

    public function restore(LeaveBalance $balance, float $days): void
    {
        $balance->update([
            'used_days' => max(0, (float) $balance->used_days - $days),
        ]);
    }
}
