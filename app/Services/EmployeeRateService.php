<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHourlyRate;
use Carbon\Carbon;

class EmployeeRateService
{
    public function rateFor(Employee $employee, Carbon $date): ?EmployeeHourlyRate
    {
        return EmployeeHourlyRate::query()
            ->where('employee_id', $employee->id)
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->first();
    }

    public function hourlyRateFor(Employee $employee, Carbon $date): float
    {
        $rate = $this->rateFor($employee, $date);

        if ($rate !== null) {
            return (float) $rate->hourly_rate;
        }

        $fallback = EmployeeHourlyRate::query()
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($date): void {
                $query->where('year', '<', $date->year)
                    ->orWhere(function ($q) use ($date): void {
                        $q->where('year', $date->year)->where('month', '<=', $date->month);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        return $fallback !== null ? (float) $fallback->hourly_rate : 0.0;
    }

    /**
     * @param  array{year: int, month: int, hourly_rate: float|string, currency?: string|null}  $data
     */
    public function upsert(Employee $employee, array $data): EmployeeHourlyRate
    {
        return EmployeeHourlyRate::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'year' => (int) $data['year'],
                'month' => (int) $data['month'],
            ],
            [
                'organization_id' => $employee->organization_id,
                'hourly_rate' => $data['hourly_rate'],
                'currency' => $data['currency'] ?? ($employee->organization?->currency ?? 'EUR'),
            ],
        );
    }
}
