<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TimesheetService
{
    public function __construct(
        protected TimeAuthorizationService $timeAuth,
    ) {}

    public function weekStart(?string $date = null, ?Organization $organization = null): CarbonInterface
    {
        $timezone = $organization?->timezone ?? config('app.timezone');
        $anchor = $date
            ? Carbon::parse($date, $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();

        return $anchor->copy()->startOfWeek(Carbon::MONDAY);
    }

    public function weekEnd(CarbonInterface $weekStart): CarbonInterface
    {
        return $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function entriesQuery(User $user, Organization $organization, CarbonInterface $weekStart, CarbonInterface $weekEnd, ?int $employeeId = null): Builder
    {
        $query = $this->timeAuth->scopeVisibleTo(
            TimeEntry::query()
                ->with(['employee', 'recordedBy'])
                ->whereBetween('clock_in', [$weekStart, $weekEnd])
                ->orderBy('clock_in'),
            $user,
            $organization,
        );

        if ($employeeId !== null && $this->canFilterByEmployee($user, $organization, $employeeId)) {
            $query->where('employee_id', $employeeId);
        }

        return $query;
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return array{
     *     total_minutes: int,
     *     break_minutes: int,
     *     regular_minutes: int,
     *     overtime_minutes: int,
     *     days_worked: int,
     *     open_entries: int,
     *     entry_count: int
     * }
     */
    public function summarize(Collection $entries, int $standardDailyMinutes = 480): array
    {
        $closed = $entries->filter(fn (TimeEntry $entry) => ! $entry->isOpen());
        $totalMinutes = $closed->sum(fn (TimeEntry $entry) => $entry->workedMinutes() ?? 0);
        $breakMinutes = $closed->sum(fn (TimeEntry $entry) => (int) $entry->break_minutes);

        $regularMinutes = 0;
        $overtimeMinutes = 0;
        $daysWorked = $closed
            ->groupBy(fn (TimeEntry $entry) => $entry->clock_in->toDateString())
            ->count();

        foreach ($closed->groupBy(fn (TimeEntry $entry) => $entry->clock_in->toDateString()) as $dayEntries) {
            $dayMinutes = $dayEntries->sum(fn (TimeEntry $entry) => $entry->workedMinutes() ?? 0);
            $regularMinutes += min($dayMinutes, $standardDailyMinutes);
            $overtimeMinutes += max(0, $dayMinutes - $standardDailyMinutes);
        }

        return [
            'total_minutes' => $totalMinutes,
            'break_minutes' => $breakMinutes,
            'regular_minutes' => $regularMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'days_worked' => $daysWorked,
            'open_entries' => $entries->filter(fn (TimeEntry $entry) => $entry->isOpen())->count(),
            'entry_count' => $entries->count(),
        ];
    }

    /**
     * @return array{content: string, filename: string}
     */
    public function buildCsvExport(
        User $user,
        Organization $organization,
        CarbonInterface $weekStart,
        CarbonInterface $weekEnd,
        ?int $employeeId = null,
    ): array {
        $entries = $this->entriesQuery($user, $organization, $weekStart, $weekEnd, $employeeId)->get();
        $summary = $this->summarize($entries);

        $rows = [];
        $rows[] = [
            'Organization',
            $organization->name,
        ];
        $rows[] = [
            'Period start',
            $weekStart->format('Y-m-d'),
        ];
        $rows[] = [
            'Period end',
            $weekEnd->format('Y-m-d'),
        ];
        $rows[] = [];
        $rows[] = [
            'Date',
            'Employee',
            'Employee ID',
            'Clock in',
            'Clock out',
            'Break (min)',
            'Hours worked',
            'Status',
            'Notes',
            'Recorded by',
        ];

        foreach ($entries as $entry) {
            $rows[] = [
                $entry->clock_in->format('Y-m-d'),
                $entry->employee->fullName(),
                $entry->employee->employee_code ?? '',
                $entry->clock_in->format('H:i'),
                $entry->clock_out?->format('H:i') ?? '',
                (string) $entry->break_minutes,
                $entry->isOpen() ? '' : $entry->workedHoursLabel(),
                $entry->isOpen() ? 'Open' : 'Completed',
                str_replace(["\r", "\n"], ' ', (string) ($entry->notes ?? '')),
                $entry->recordedBy?->name ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Summary'];
        $rows[] = ['Total hours', $this->minutesLabel($summary['total_minutes'])];
        $rows[] = ['Regular hours', $this->minutesLabel($summary['regular_minutes'])];
        $rows[] = ['Overtime hours', $this->minutesLabel($summary['overtime_minutes'])];
        $rows[] = ['Break minutes', (string) $summary['break_minutes']];
        $rows[] = ['Days worked', (string) $summary['days_worked']];

        $content = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $content .= implode(',', array_map([$this, 'csvCell'], $row))."\n";
        }

        return [
            'content' => $content,
            'filename' => sprintf(
                'timesheet-%s-%s.csv',
                $organization->slug,
                $weekStart->format('Y-m-d'),
            ),
        ];
    }

    public function minutesLabel(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * @return Collection<int, Employee>
     */
    public function filterableEmployees(User $user, Organization $organization): Collection
    {
        if ($this->timeAuth->canViewAll($user, $organization)) {
            return Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        if ($this->timeAuth->canViewTeam($user, $organization)) {
            $managerProfile = app(EmployeeProfileService::class)->employeeFor($user, $organization);

            if ($managerProfile === null) {
                return collect();
            }

            return Employee::query()
                ->where(function ($query) use ($managerProfile): void {
                    $query->where('id', $managerProfile->id)
                        ->orWhere('manager_id', $managerProfile->id);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        $self = app(EmployeeProfileService::class)->employeeFor($user, $organization);

        return $self ? collect([$self]) : collect();
    }

    /**
     * @return array{week?: string, employee?: string}
     */
    public function indexQueryParams(CarbonInterface $weekStart, ?Employee $employee = null): array
    {
        return array_filter([
            'week' => $weekStart->toDateString(),
            'employee' => $employee?->employee_code,
        ]);
    }

    public function resolveEmployeeFilter(Request $request, User $user, Organization $organization): ?Employee
    {
        $filterable = $this->filterableEmployees($user, $organization);

        if ($request->filled('employee')) {
            return $filterable->firstWhere('employee_code', $request->string('employee')->toString());
        }

        if ($request->filled('employee_id')) {
            return $filterable->firstWhere('id', $request->integer('employee_id'));
        }

        return null;
    }

    protected function canFilterByEmployee(User $user, Organization $organization, int $employeeId): bool
    {
        return $this->filterableEmployees($user, $organization)->contains('id', $employeeId);
    }

    protected function csvCell(mixed $value): string
    {
        $string = (string) $value;

        if (str_contains($string, ',') || str_contains($string, '"') || str_contains($string, "\n")) {
            return '"'.str_replace('"', '""', $string).'"';
        }

        return $string;
    }
}
