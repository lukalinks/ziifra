<?php

namespace App\Services;

use App\Enums\CompensationType;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PayrollTimeService
{
    public function __construct(
        protected EmployeeRateService $rates,
    ) {}

    /**
     * @return array{
     *     year: int,
     *     month: int,
     *     project: Project|null,
     *     editable: bool,
     *     days: list<Carbon>,
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, float>
     * }
     */
    public function grid(
        Organization $organization,
        int $year,
        int $month,
        ?int $projectId = null,
        ?string $search = null,
    ): array {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $days = collect(CarbonPeriod::create($start, $end))->values()->all();

        $project = $projectId
            ? Project::query()->where('organization_id', $organization->id)->find($projectId)
            : null;

        // Daily hours can only be edited when a single project is selected,
        // because each hours entry must belong to a project.
        $editable = $project !== null;

        $employeesQuery = Employee::query()
            ->where('organization_id', $organization->id)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search) {
            $employeesQuery->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($project) {
            $employeesQuery->whereHas('projects', fn ($q) => $q->where('projects.id', $project->id));
        }

        $employees = $employeesQuery->get();
        $employeeIds = $employees->pluck('id');

        $entriesQuery = DailyHoursEntry::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $employeeIds);

        if ($project) {
            $entriesQuery->where('project_id', $project->id);
        }

        $entries = $entriesQuery->get()->groupBy(fn ($e) => $e->employee_id.'|'.$e->work_date->format('Y-m-d'));

        $settings = $organization->resolvedPayrollSettings();
        $defaultTrustEmployee = (float) ($settings['trust_employee_percent'] ?? 5);
        $defaultTrustEmployer = (float) ($settings['trust_employer_percent'] ?? 5);

        $rows = [];
        $grandHours = 0.0;
        $grandGross = 0.0;
        $grandTrustEmployee = 0.0;
        $grandTrustEmployer = 0.0;
        $grandNet = 0.0;

        foreach ($employees as $employee) {
            $daily = [];
            $totalHours = 0.0;
            $rate = $this->rates->hourlyRateFor($employee, $start);
            $currency = $this->rates->hourlyCurrencyFor($employee);

            foreach ($days as $day) {
                $key = $employee->id.'|'.$day->format('Y-m-d');
                $dayEntries = $entries->get($key, collect());
                $daily[$day->format('Y-m-d')] = (float) $dayEntries->sum('hours');
                $totalHours += $daily[$day->format('Y-m-d')];
            }

            $isMonthly = $employee->compensation_type === CompensationType::Monthly && $employee->fixed_monthly_salary;
            $gross = $isMonthly
                ? (float) $employee->fixed_monthly_salary
                : round($totalHours * $rate, 2);

            $trustEmployeePct = $employee->trust_override_percent !== null
                ? (float) $employee->trust_override_percent
                : $defaultTrustEmployee;
            $trustEmployerPct = $defaultTrustEmployer;

            $trustEmployee = round($gross * ($trustEmployeePct / 100), 2);
            $trustEmployer = round($gross * ($trustEmployerPct / 100), 2);
            $net = round($gross - $trustEmployee, 2);

            $grandHours += $totalHours;
            $grandGross += $gross;
            $grandTrustEmployee += $trustEmployee;
            $grandTrustEmployer += $trustEmployer;
            $grandNet += $net;

            $rows[] = [
                'employee' => $employee,
                'daily' => $daily,
                'total_hours' => $totalHours,
                'hourly_rate' => $rate,
                'currency' => $currency,
                'is_monthly' => (bool) $isMonthly,
                'compensation_type' => $employee->compensation_type?->value,
                'gross' => $gross,
                'trust_employee' => $trustEmployee,
                'trust_employer' => $trustEmployer,
                'net' => $net,
                'trust_employee_percent' => $trustEmployeePct,
                'trust_employer_percent' => $trustEmployerPct,
                'trust_is_override' => $employee->trust_override_percent !== null,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'project' => $project,
            'editable' => $editable,
            'days' => $days,
            'rows' => $rows,
            'totals' => [
                'hours' => $grandHours,
                'gross' => $grandGross,
                'trust_employee' => $grandTrustEmployee,
                'trust_employer' => $grandTrustEmployer,
                'net' => $grandNet,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function availableYears(): array
    {
        $current = (int) now()->year;

        return range($current, $current + 10);
    }
}
