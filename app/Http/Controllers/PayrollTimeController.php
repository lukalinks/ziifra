<?php

namespace App\Http\Controllers;

use App\Enums\CompensationType;
use App\Http\Requests\PayrollTimeUpsertRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DailyHoursService;
use App\Services\EmployeeProfileService;
use App\Services\PayrollTimeArchiveService;
use App\Services\PayrollTimeExportService;
use App\Services\PayrollTimeService;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollTimeController extends Controller
{
    public function index(Request $request, PayrollTimeService $payrollTime): View
    {
        $this->authorize('viewAny', Employee::class);

        $organization = CurrentOrganization::check();
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);

        $projectId = $request->has('project_id')
            ? ($request->integer('project_id') ?: null)
            : Project::query()->orderBy('name')->value('id');

        $grid = $payrollTime->grid(
            $organization,
            $year,
            $month,
            $projectId,
            $request->string('search')->trim()->toString() ?: null,
        );

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);
        $archive = app(PayrollTimeArchiveService::class);
        $linkedEmployee = app(EmployeeProfileService::class)->employeeFor($request->user(), $organization);
        $canManage = $request->user()->can('create', Employee::class);

        return view('app.payroll-time.index', [
            'organization' => $organization,
            'grid' => $grid,
            'year' => $year,
            'month' => $month,
            'years' => $payrollTime->availableYears(),
            'projects' => $projects,
            'search' => $request->string('search')->trim()->toString(),
            'canManage' => $canManage,
            'linkedEmployee' => $linkedEmployee,
            'canApprove' => $canManage,
            'payrollSettings' => $organization->resolvedPayrollSettings(),
            'payrollFolder' => $archive->payrollFolder($organization),
        ]);
    }

    public function approveEmployee(
        Request $request,
        Organization $organization,
        Employee $employee,
        DailyHoursService $hours,
    ): RedirectResponse {
        $this->authorize('create', Employee::class);
        abort_unless($employee->organization_id === $organization->id, 404);

        $month = Carbon::create(
            (int) $request->integer('year', now()->year),
            (int) $request->integer('month', now()->month),
            1,
        )->startOfMonth();

        $projectId = $request->filled('project_id') ? $request->integer('project_id') : null;

        $count = $hours->approveEmployeeInPeriod(
            $organization->id,
            $employee,
            $month,
            $request->user(),
            $projectId,
        );

        return redirect()
            ->route('payroll-time.index', array_filter([
                'organization' => $organization,
                'year' => $month->year,
                'month' => $month->month,
                'project_id' => $projectId,
                'search' => $request->string('search')->trim()->toString() ?: null,
            ]))
            ->with('status', __('daily_hours.approved_count', ['count' => $count]));
    }

    public function approveAll(
        Request $request,
        Organization $organization,
        DailyHoursService $hours,
    ): RedirectResponse {
        $this->authorize('create', Employee::class);

        $month = Carbon::create(
            (int) $request->integer('year', now()->year),
            (int) $request->integer('month', now()->month),
            1,
        )->startOfMonth();

        $projectId = $request->filled('project_id') ? $request->integer('project_id') : null;

        $count = $hours->approveAllInPeriod(
            $organization->id,
            $month,
            $request->user(),
            $projectId,
        );

        return redirect()
            ->route('payroll-time.index', array_filter([
                'organization' => $organization,
                'year' => $month->year,
                'month' => $month->month,
                'project_id' => $projectId,
                'search' => $request->string('search')->trim()->toString() ?: null,
            ]))
            ->with('status', __('daily_hours.approved_count', ['count' => $count]));
    }

    public function upsert(
        PayrollTimeUpsertRequest $request,
        Organization $organization,
        DailyHoursService $hours,
    ): JsonResponse {
        /** @var Employee $employee */
        $employee = Employee::query()
            ->where('organization_id', $organization->id)
            ->findOrFail($request->integer('employee_id'));
        /** @var Project $project */
        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->findOrFail($request->integer('project_id'));

        abort_unless($project->members()->whereKey($employee->id)->exists(), 422);

        $entry = $hours->upsertCell(
            $project,
            $employee,
            Carbon::parse($request->validated('work_date')),
            (float) $request->validated('hours'),
        );

        return response()->json([
            'id' => $entry->id,
            'hours' => (float) $entry->hours,
            'approval_status' => $entry->approval_status->value,
            'message' => $entry->approval_status->value === 'pending'
                ? __('my_hours.submitted_for_approval')
                : null,
        ]);
    }

    public function updateRate(Request $request, Organization $organization, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);
        abort_unless($employee->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'fixed_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fixed_hourly_currency' => ['nullable', 'string', 'size:3'],
            'trust_override_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $attributes = [];

        if ($request->has('fixed_hourly_rate')) {
            $attributes['fixed_hourly_rate'] = $validated['fixed_hourly_rate'];
            $attributes['fixed_hourly_currency'] = $validated['fixed_hourly_currency']
                ?? $employee->fixed_hourly_currency
                ?? $organization->currency;
            $attributes['compensation_type'] = CompensationType::Hourly;
        }

        if ($request->has('trust_override_percent')) {
            $attributes['trust_override_percent'] = $validated['trust_override_percent'];
        }

        if ($attributes !== []) {
            $employee->update($attributes);
        }

        return response()->json([
            'fixed_hourly_rate' => $employee->fixed_hourly_rate !== null ? (float) $employee->fixed_hourly_rate : null,
            'trust_override_percent' => $employee->trust_override_percent !== null ? (float) $employee->trust_override_percent : null,
        ]);
    }

    public function exportPdf(Request $request, PayrollTimeExportService $export, PayrollTimeArchiveService $archive): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('viewAny', Employee::class);

        $organization = CurrentOrganization::check();
        $year = (int) $request->integer('year', now()->year);
        $month = $request->filled('month') ? (int) $request->integer('month') : null;
        $projectId = $request->integer('project_id') ?: null;

        if ($request->boolean('archive') && $month !== null) {
            $this->authorize('create', Employee::class);
            $archive->archiveMonth($organization, $year, $month, $projectId, $request->user());
            $folder = $archive->payrollFolder($organization);

            return redirect()
                ->route('documents.index', ['organization' => $organization, 'folder' => $folder->id])
                ->with('status', __('payroll_time.archived_to_documents'));
        }

        return $export->pdf($organization, $year, $month, $projectId);
    }

    public function archivePastMonths(Request $request, Organization $organization, PayrollTimeArchiveService $archive): RedirectResponse
    {
        $this->authorize('create', Employee::class);
        abort_unless($organization->id === CurrentOrganization::id(), 404);

        $year = (int) $request->integer('year', now()->year);
        $projectId = $request->integer('project_id') ?: null;
        $count = $archive->archivePastMonthsInYear($organization, $year, $request->user(), $projectId);
        $folder = $archive->payrollFolder($organization);

        return redirect()
            ->route('documents.index', ['organization' => $organization, 'folder' => $folder->id])
            ->with('status', __('payroll_time.archived_past_months', ['count' => $count]));
    }

    public function exportExcel(Request $request, PayrollTimeExportService $export)
    {
        $this->authorize('viewAny', Employee::class);

        return $export->excel(
            CurrentOrganization::check(),
            (int) $request->integer('year', now()->year),
            $request->filled('month') ? (int) $request->integer('month') : null,
            $request->integer('project_id') ?: null,
        );
    }

    public function exportEmployeePdf(
        Request $request,
        Organization $organization,
        Employee $employee,
        PayrollTimeExportService $export,
    ) {
        $this->authorize('view', $employee);
        abort_unless($employee->organization_id === $organization->id, 404);

        return $export->employeePdf(
            $organization,
            $employee,
            (int) $request->integer('year', now()->year),
            $request->filled('month') ? (int) $request->integer('month') : null,
            $request->integer('project_id') ?: null,
        );
    }

    public function exportEmployeeExcel(
        Request $request,
        Organization $organization,
        Employee $employee,
        PayrollTimeExportService $export,
    ) {
        $this->authorize('view', $employee);
        abort_unless($employee->organization_id === $organization->id, 404);

        return $export->employeeExcel(
            $organization,
            $employee,
            (int) $request->integer('year', now()->year),
            $request->filled('month') ? (int) $request->integer('month') : null,
            $request->integer('project_id') ?: null,
        );
    }
}
