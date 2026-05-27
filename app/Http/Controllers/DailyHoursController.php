<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertDailyHoursRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DailyHoursService;
use App\Services\ProjectHoursExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyHoursController extends Controller
{
    public function upsert(
        UpsertDailyHoursRequest $request,
        Organization $organization,
        Project $project,
        DailyHoursService $hours,
    ): JsonResponse {
        $this->authorize('update', $project);

        /** @var Employee $employee */
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

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

    public function approveRow(
        Request $request,
        Organization $organization,
        Project $project,
        Employee $employee,
        DailyHoursService $hours,
    ): RedirectResponse {
        $this->authorize('update', $project);

        abort_unless($project->members()->whereKey($employee->id)->exists(), 404);

        $month = Carbon::parse($request->string('month')->toString() ?: now()->format('Y-m'))->startOfMonth();
        $count = $hours->approveEmployeeMonth($project, $employee, $month, $request->user());

        return back()->with('status', __('daily_hours.approved_count', ['count' => $count]));
    }

    public function approveAll(
        Request $request,
        Organization $organization,
        Project $project,
        DailyHoursService $hours,
    ): RedirectResponse {
        $this->authorize('update', $project);

        $month = Carbon::parse($request->string('month')->toString() ?: now()->format('Y-m'))->startOfMonth();
        $count = $hours->approveAllMonth($project, $month, $request->user());

        return back()->with('status', __('daily_hours.approved_count', ['count' => $count]));
    }

    public function export(
        Organization $organization,
        Project $project,
        Request $request,
        ProjectHoursExportService $export,
    ) {
        $this->authorize('view', $project);

        $month = Carbon::parse($request->string('month')->toString() ?: now()->format('Y-m'))->startOfMonth();

        return $export->exportCsv($project, $month);
    }
}
