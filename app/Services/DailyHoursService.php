<?php

namespace App\Services;

use App\Enums\DailyHoursApprovalStatus;
use App\Models\DailyHoursEntry;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyHoursService
{
    public const STANDARD_DAY_HOURS = 8;

    public function __construct(
        protected EmployeeRateService $rates,
    ) {}

    /**
     * @return array{
     *     month: string,
     *     days: list<int>,
     *     employees: Collection<int, Employee>,
     *     grid: array<int, array<int, DailyHoursEntry|null>>,
     *     rows: array<int, array{hours: float, pay: float, rate: float, status: string}>,
     *     currency: string,
     *     totals: array{hours: float, pending: int, payroll: float, pending_employees: int}
     * }
     */
    public function gridForProject(Project $project, Carbon $month, ?string $search = null): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $days = range(1, (int) $end->day);

        $employees = $project->members()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->when($search, fn ($query, string $search) => $query->matchingSearch($search))
            ->get();

        $entries = DailyHoursEntry::query()
            ->where('project_id', $project->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (DailyHoursEntry $entry) => $entry->employee_id.'-'.$entry->work_date->day);

        $grid = [];
        $rows = [];
        $totalHours = 0.0;
        $pending = 0;
        $projectedPayroll = 0.0;
        $pendingEmployees = 0;
        $currency = $project->currency ?? $project->organization?->currency ?? 'EUR';

        foreach ($employees as $employee) {
            $grid[$employee->id] = [];
            $rowTotal = 0.0;
            $hasHours = false;
            $hasPending = false;
            $hourlyRate = $this->rates->hourlyRateFor($employee, $start);

            foreach ($days as $day) {
                $key = $employee->id.'-'.$day;
                $entry = $entries->get($key)?->first();
                $grid[$employee->id][$day] = $entry;

                if ($entry !== null) {
                    $hours = (float) $entry->hours;
                    $totalHours += $hours;
                    $rowTotal += $hours;

                    if ($hours > 0) {
                        $hasHours = true;
                    }

                    if ($entry->approval_status === DailyHoursApprovalStatus::Pending) {
                        $pending++;

                        if ($hours > 0) {
                            $hasPending = true;
                        }
                    }
                }
            }

            $rowPay = round($rowTotal * $hourlyRate, 2);
            $projectedPayroll += $rowPay;

            if ($hasPending) {
                $pendingEmployees++;
            }

            $rows[$employee->id] = [
                'hours' => round($rowTotal, 2),
                'pay' => $rowPay,
                'rate' => $hourlyRate,
                'status' => ! $hasHours ? 'empty' : ($hasPending ? 'pending' : 'approved'),
            ];
        }

        return [
            'month' => $start->format('Y-m'),
            'days' => $days,
            'employees' => $employees,
            'grid' => $grid,
            'rows' => $rows,
            'currency' => $currency,
            'totals' => [
                'hours' => round($totalHours, 2),
                'pending' => $pending,
                'payroll' => round($projectedPayroll, 2),
                'pending_employees' => $pendingEmployees,
            ],
        ];
    }

    public function upsertCell(
        Project $project,
        Employee $employee,
        Carbon $date,
        float $hours,
    ): DailyHoursEntry {
        abort_unless($project->members()->whereKey($employee->id)->exists(), 422);

        $hours = max(0, min(24, round($hours, 2)));
        $dateString = $date->toDateString();

        $entry = DailyHoursEntry::query()
            ->where('employee_id', $employee->id)
            ->where('project_id', $project->id)
            ->whereDate('work_date', $dateString)
            ->first();

        if ($entry !== null) {
            $entry->update([
                'hours' => $hours,
                'approval_status' => DailyHoursApprovalStatus::Pending,
                'approved_by_user_id' => null,
                'approved_at' => null,
            ]);

            return $entry->fresh();
        }

        return DailyHoursEntry::query()->create([
            'organization_id' => $project->organization_id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'work_date' => $dateString,
            'hours' => $hours,
            'approval_status' => DailyHoursApprovalStatus::Pending,
        ]);
    }

    public function approveEmployeeMonth(Project $project, Employee $employee, Carbon $month, User $approver): int
    {
        return $this->approveEmployeeInPeriod(
            $project->organization_id,
            $employee,
            $month,
            $approver,
            $project->id,
        );
    }

    public function approveAllMonth(Project $project, Carbon $month, User $approver): int
    {
        return $this->approveAllInPeriod(
            $project->organization_id,
            $month,
            $approver,
            $project->id,
        );
    }

    public function approveEmployeeInPeriod(
        int $organizationId,
        Employee $employee,
        Carbon $month,
        User $approver,
        ?int $projectId = null,
    ): int {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return DailyHoursEntry::query()
            ->where('organization_id', $organizationId)
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->where('approval_status', DailyHoursApprovalStatus::Pending)
            ->where('hours', '>', 0)
            ->update([
                'approval_status' => DailyHoursApprovalStatus::Approved,
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
            ]);
    }

    public function approveAllInPeriod(
        int $organizationId,
        Carbon $month,
        User $approver,
        ?int $projectId = null,
    ): int {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return DailyHoursEntry::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->where('approval_status', DailyHoursApprovalStatus::Pending)
            ->where('hours', '>', 0)
            ->update([
                'approval_status' => DailyHoursApprovalStatus::Approved,
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
            ]);
    }

    /**
     * @return array{total_hours: float, pending_count: int}
     */
    public function organizationStats(int $organizationId, ?Carbon $month = null): array
    {
        $month ??= now();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $query = DailyHoursEntry::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()]);

        return [
            'total_hours' => round((float) (clone $query)->sum('hours'), 2),
            'pending_count' => (clone $query)->where('approval_status', DailyHoursApprovalStatus::Pending)->count(),
        ];
    }

    /**
     * @return Collection<int, DailyHoursEntry>
     */
    public function approvedEntriesForPeriod(
        int $organizationId,
        Carbon $start,
        Carbon $end,
        ?int $projectId = null,
        ?array $employeeIds = null,
    ): Collection {
        return DailyHoursEntry::query()
            ->with(['employee', 'project'])
            ->where('organization_id', $organizationId)
            ->where('approval_status', DailyHoursApprovalStatus::Approved)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($employeeIds, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->orderBy('work_date')
            ->get();
    }
}
