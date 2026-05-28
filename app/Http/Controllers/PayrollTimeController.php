<?php

namespace App\Http\Controllers;

use App\Enums\CompensationType;
use App\Http\Requests\PayrollTimeUpsertRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DailyHoursService;
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
        $projectId = $request->integer('project_id') ?: null;

        $grid = $payrollTime->grid(
            $organization,
            $year,
            $month,
            $projectId,
            $request->string('search')->trim()->toString() ?: null,
        );

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);

        return view('app.payroll-time.index', [
            'organization' => $organization,
            'grid' => $grid,
            'year' => $year,
            'month' => $month,
            'years' => $payrollTime->availableYears(),
            'projects' => $projects,
            'search' => $request->string('search')->trim()->toString(),
            'canManage' => $request->user()->can('create', Employee::class),
            'payrollSettings' => $organization->resolvedPayrollSettings(),
        ]);
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

    public function exportPdf(Request $request, PayrollTimeExportService $export)
    {
        $this->authorize('viewAny', Employee::class);

        return $export->pdf(
            CurrentOrganization::check(),
            (int) $request->integer('year', now()->year),
            $request->filled('month') ? (int) $request->integer('month') : null,
            $request->integer('project_id') ?: null,
        );
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
