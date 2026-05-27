<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\PayrollGenerationMode;
use App\Enums\PayrollRunStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HourlyPayrollService
{
    public function __construct(
        protected DailyHoursService $hours,
        protected EmployeeRateService $rates,
        protected KosovoPayrollCalculator $calculator,
    ) {}

    /**
     * @param  list<int>|null  $employeeIds
     */
    public function create(
        Organization $organization,
        int $year,
        int $month,
        PayrollGenerationMode $mode = PayrollGenerationMode::All,
        ?array $employeeIds = null,
    ): PayrollRun {
        if (PayrollRun::query()
            ->where('organization_id', $organization->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists()) {
            throw ValidationException::withMessages([
                'month' => __('payroll.errors.period_exists'),
            ]);
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $employeesQuery = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employment_status', EmploymentStatus::Active)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($mode === PayrollGenerationMode::Individual && $employeeIds !== null && count($employeeIds) === 1) {
            $employeesQuery->whereKey($employeeIds[0]);
        } elseif ($mode === PayrollGenerationMode::Group && $employeeIds !== null && $employeeIds !== []) {
            $employeesQuery->whereIn('id', $employeeIds);
        }

        $employees = $employeesQuery->get();

        return DB::transaction(function () use ($organization, $year, $month, $start, $end, $employees, $mode, $employeeIds): PayrollRun {
            $run = PayrollRun::query()->create([
                'organization_id' => $organization->id,
                'year' => $year,
                'month' => $month,
                'status' => PayrollRunStatus::Draft,
                'rules_snapshot' => array_merge(config('payroll.kosovo'), [
                    'calculation_mode' => 'hourly',
                    'generation_mode' => $mode->value,
                    'employee_ids' => $employeeIds,
                ]),
            ]);

            foreach ($employees as $employee) {
                $entries = $this->hours->approvedEntriesForPeriod(
                    $organization->id,
                    $start,
                    $end,
                    null,
                    [$employee->id],
                );

                $totalHours = round((float) $entries->sum('hours'), 2);
                $rate = $this->rates->hourlyRateFor($employee, $start);
                $gross = round($totalHours * $rate, 2);

                $calc = $this->calculator->calculate($gross, []);

                PayrollItem::query()->create([
                    'organization_id' => $organization->id,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'hours_worked' => $totalHours,
                    'hourly_rate' => $rate,
                    'base_gross_salary' => $gross,
                    'allowances' => 0,
                    'exempt_allowances_total' => 0,
                    'gross_salary' => $gross,
                    'employee_pension' => $calc['employee_pension'],
                    'employer_pension' => $calc['employer_pension'],
                    'income_tax' => $calc['income_tax'],
                    'net_salary' => $calc['net_salary'],
                    'employee_snapshot' => [
                        'name' => $employee->fullName(),
                        'email' => $employee->email,
                        'employee_code' => $employee->displayCode(),
                        'hours_worked' => $totalHours,
                        'hourly_rate' => $rate,
                    ],
                ]);
            }

            return $run->load(['items.employee']);
        });
    }
}
