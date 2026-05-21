<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\ExpenseClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\PayrollRunStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TimeEntry;
use App\Models\User;

class ReportService
{
    public function __construct(
        protected ReportAuthorizationService $reportAuth,
        protected AdminDashboardService $adminDashboard,
        protected OrganizationBillingService $billing,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function compile(User $user, Organization $organization): array
    {
        $scopedEmployeeIds = $this->reportAuth->scopedEmployeeIds($user, $organization);
        $fullAccess = $this->reportAuth->hasFullAccess($user, $organization);
        $canViewFinance = $this->reportAuth->canViewFinance($user, $organization);
        $hasPayroll = $this->billing->hasPayroll($organization);

        $employeeQuery = Employee::query()->where('employment_status', EmploymentStatus::Active);

        if ($scopedEmployeeIds !== null) {
            $employeeQuery->whereIn('id', $scopedEmployeeIds);
        }

        $activeEmployeeCount = (clone $employeeQuery)->count();

        $newHiresQuery = Employee::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($scopedEmployeeIds !== null) {
            $newHiresQuery->whereIn('id', $scopedEmployeeIds);
        }

        $leaveQuery = LeaveRequest::query();

        if ($scopedEmployeeIds !== null) {
            $leaveQuery->whereIn('employee_id', $scopedEmployeeIds);
        }

        $pendingLeaveCount = (clone $leaveQuery)
            ->where('status', LeaveRequestStatus::Pending)
            ->count();

        $approvedLeaveDaysMonth = (float) (clone $leaveQuery)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->sum('days');

        $departments = $this->departmentBreakdown($scopedEmployeeIds);

        $payload = [
            'scope' => $fullAccess ? 'company' : 'team',
            'generatedAt' => now(),
            'workforce' => [
                'active_employees' => $activeEmployeeCount,
                'new_hires_month' => $newHiresQuery->count(),
                'departments' => $departments,
            ],
            'leave' => [
                'pending' => $pendingLeaveCount,
                'approved_days_month' => $approvedLeaveDaysMonth,
                'trend' => $fullAccess
                    ? $this->adminDashboard->monthlyLeaveTrend($organization)
                    : $this->scopedLeaveTrend($scopedEmployeeIds ?? []),
            ],
            'finance' => null,
            'payroll' => null,
            'work' => $this->workMetrics($scopedEmployeeIds),
            'hasPayroll' => $hasPayroll,
            'canViewFinance' => $canViewFinance,
        ];

        if ($canViewFinance) {
            $payload['finance'] = [
                'invoices_unpaid_count' => Invoice::query()
                    ->where('status', InvoiceStatus::Sent)
                    ->count(),
                'invoices_unpaid_total' => (float) Invoice::query()
                    ->where('status', InvoiceStatus::Sent)
                    ->sum('amount'),
                'expenses_pending_count' => ExpenseClaim::query()
                    ->where('status', ExpenseClaimStatus::Pending)
                    ->count(),
                'expenses_approved_month' => (float) ExpenseClaim::query()
                    ->where('status', ExpenseClaimStatus::Approved)
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'),
            ];
        }

        if ($hasPayroll && $fullAccess) {
            $latestRun = PayrollRun::query()->orderByDesc('year')->orderByDesc('month')->first();

            $payload['payroll'] = [
                'latest_run' => $latestRun?->periodLabel(),
                'latest_status' => $latestRun?->status->label(),
                'locked_runs' => PayrollRun::query()->where('status', PayrollRunStatus::Locked)->count(),
                'gross_last_month' => $this->payrollGrossForMonth(
                    (int) now()->subMonth()->year,
                    (int) now()->subMonth()->month,
                ),
            ];
        }

        return $payload;
    }

    /**
     * @param  list<int>|null  $scopedEmployeeIds
     * @return list<array{label: string, count: int}>
     */
    protected function departmentBreakdown(?array $scopedEmployeeIds): array
    {
        $query = Employee::query()
            ->where('employment_status', EmploymentStatus::Active)
            ->whereNotNull('department_id');

        if ($scopedEmployeeIds !== null) {
            $query->whereIn('id', $scopedEmployeeIds);
        }

        $counts = $query
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        if ($counts->isEmpty()) {
            return [];
        }

        $departments = Department::query()->whereIn('id', $counts->keys())->get()->keyBy('id');

        return $counts
            ->map(fn (int $count, int $departmentId) => [
                'label' => $departments->get($departmentId)?->name ?? 'Unknown',
                'count' => $count,
            ])
            ->sortByDesc('count')
            ->values()
            ->take(6)
            ->all();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array{labels: list<string>, approved: list<float>, pending: list<int>}
     */
    protected function scopedLeaveTrend(array $employeeIds): array
    {
        $labels = [];
        $approved = [];
        $pending = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $base = LeaveRequest::query()->whereIn('employee_id', $employeeIds);

            $approved[] = (float) (clone $base)
                ->where('status', LeaveRequestStatus::Approved)
                ->whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year)
                ->sum('days');

            $pending[] = (clone $base)
                ->where('status', LeaveRequestStatus::Pending)
                ->whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year)
                ->count();
        }

        return compact('labels', 'approved', 'pending');
    }

    /**
     * @param  list<int>|null  $scopedEmployeeIds
     * @return array<string, int|float|null>
     */
    protected function workMetrics(?array $scopedEmployeeIds): array
    {
        $activeProjects = Project::query()
            ->where('status', ProjectStatus::Active)
            ->count();

        $totalTasks = ProjectTask::query()->count();
        $doneTasks = ProjectTask::query()->where('status', ProjectTaskStatus::Done)->count();
        $taskCompletion = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        $timeQuery = TimeEntry::query()->whereNotNull('clock_out');

        if ($scopedEmployeeIds !== null) {
            $timeQuery->whereIn('employee_id', $scopedEmployeeIds);
        }

        $hoursMonth = (int) (clone $timeQuery)
            ->whereMonth('clock_in', now()->month)
            ->whereYear('clock_in', now()->year)
            ->get()
            ->sum(fn (TimeEntry $entry) => $entry->workedMinutes() ?? 0);

        $openEntries = TimeEntry::query()->whereNull('clock_out');

        if ($scopedEmployeeIds !== null) {
            $openEntries->whereIn('employee_id', $scopedEmployeeIds);
        }

        return [
            'active_projects' => $activeProjects,
            'task_completion_percent' => $taskCompletion,
            'open_time_entries' => $openEntries->count(),
            'hours_logged_month' => intdiv($hoursMonth, 60),
        ];
    }

    protected function payrollGrossForMonth(int $year, int $month): float
    {
        $run = PayrollRun::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', PayrollRunStatus::Locked)
            ->first();

        if ($run === null) {
            return 0.0;
        }

        return (float) PayrollItem::query()
            ->where('payroll_run_id', $run->id)
            ->sum('gross_salary');
    }

    /**
     * Flatten the report as CSV (UTF-8, Excel-friendly BOM) using the active locale for labels.
     *
     * @return array{content: string, filename: string}
     */
    public function buildCsvExport(User $user, Organization $organization): array
    {
        $report = $this->compile($user, $organization);
        $tz = $organization->timezone ?? config('app.timezone');
        $currency = $organization->currency ?? 'EUR';
        $lines = [];

        $row = fn (string $section, string $label, string $value) => $lines[] = $this->csvCells([$section, $label, $value]);

        $row(__('reports.export_section_meta'), __('reports.export_label_organization'), $organization->name);
        $row(__('reports.export_section_meta'), __('reports.export_label_generated'), $report['generatedAt']->timezone($tz)->format('c'));
        $row(
            __('reports.export_section_meta'),
            __('reports.export_label_scope'),
            ($report['scope'] ?? 'company') === 'team' ? __('reports.scope_team') : __('reports.scope_company'),
        );

        $row(__('reports.workforce'), __('reports.active_employees'), (string) $report['workforce']['active_employees']);
        $row(__('reports.workforce'), __('reports.new_hires_month'), (string) $report['workforce']['new_hires_month']);

        foreach ($report['workforce']['departments'] as $dept) {
            $row(
                __('reports.workforce').' / '.__('reports.by_department'),
                $dept['label'],
                (string) $dept['count'],
            );
        }

        $row(__('reports.leave'), __('reports.pending_leave'), (string) $report['leave']['pending']);
        $row(
            __('reports.leave'),
            __('reports.approved_days_month'),
            (string) $report['leave']['approved_days_month'],
        );

        foreach ($report['leave']['trend']['labels'] as $i => $label) {
            $row(
                __('reports.leave_trend'),
                $label.' / '.__('reports.approved_days'),
                (string) ($report['leave']['trend']['approved'][$i] ?? 0),
            );
            $row(
                __('reports.leave_trend'),
                $label.' / '.__('reports.pending_requests'),
                (string) ($report['leave']['trend']['pending'][$i] ?? 0),
            );
        }

        if (is_array($report['finance'])) {
            $row(
                __('reports.finance'),
                __('reports.invoices_unpaid'),
                (string) $report['finance']['invoices_unpaid_count'],
            );
            $row(
                __('reports.finance'),
                __('reports.export_invoices_unpaid_total'),
                number_format($report['finance']['invoices_unpaid_total'], 2).' '.$currency,
            );
            $row(
                __('reports.finance'),
                __('reports.expenses_pending'),
                (string) $report['finance']['expenses_pending_count'],
            );
            $row(
                __('reports.finance'),
                __('reports.expenses_approved_month'),
                number_format($report['finance']['expenses_approved_month'], 2).' '.$currency,
            );
        }

        if (is_array($report['payroll'])) {
            $row(__('reports.payroll'), __('reports.latest_payroll'), (string) ($report['payroll']['latest_run'] ?? '—'));
            $row(__('reports.payroll'), __('reports.export_latest_payroll_status'), (string) ($report['payroll']['latest_status'] ?? ''));
            $row(__('reports.payroll'), __('reports.locked_runs'), (string) $report['payroll']['locked_runs']);
            $row(
                __('reports.payroll'),
                __('reports.gross_last_month'),
                number_format($report['payroll']['gross_last_month'], 2).' '.$currency,
            );
        }

        $row(__('reports.work'), __('reports.active_projects'), (string) $report['work']['active_projects']);
        $row(__('reports.work'), __('reports.task_completion'), (string) $report['work']['task_completion_percent'].'%');
        $row(__('reports.work'), __('reports.open_time_entries'), (string) $report['work']['open_time_entries']);
        $row(__('reports.work'), __('reports.hours_logged_month'), (string) $report['work']['hours_logged_month'].'h');

        $body = implode("\n", $lines);
        $header = $this->csvCells([
            __('reports.export_column_section'),
            __('reports.export_column_label'),
            __('reports.export_column_value'),
        ]);

        $filename = 'ziifra-report-'.$organization->slug.'-'.$report['generatedAt']->timezone($tz)->format('Y-m-d-His').'.csv';

        return [
            'content' => "\xEF\xBB\xBF".$header."\n".$body,
            'filename' => $filename,
        ];
    }

    /**
     * @throws \JsonException
     *
     * @return array{content: string, filename: string}
     */
    public function buildJsonExport(User $user, Organization $organization): array
    {
        $report = $this->compile($user, $organization);
        $tz = $organization->timezone ?? config('app.timezone');

        $payload = $report;
        $payload['generatedAt'] = $report['generatedAt']->timezone($tz)->toIso8601String();
        $payload['organization'] = [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'currency' => $organization->currency,
            'timezone' => $organization->timezone,
        ];

        $filename = 'ziifra-report-'.$organization->slug.'-'.$report['generatedAt']->timezone($tz)->format('Y-m-d-His').'.json';

        return [
            'content' => json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'filename' => $filename,
        ];
    }

    /**
     * @param  list<string|int|float>  $cells
     */
    protected function csvCells(array $cells): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, array_map(fn ($v) => (string) $v, $cells));
        rewind($handle);

        return rtrim(stream_get_contents($handle) ?: '', "\n");
    }
}
