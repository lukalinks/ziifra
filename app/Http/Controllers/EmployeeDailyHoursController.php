<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollTimeUpsertRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DailyHoursService;
use App\Services\EmployeeProfileService;
use App\Services\PayrollTimeService;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeDailyHoursController extends Controller
{
    public function index(Request $request, EmployeeProfileService $profiles, PayrollTimeService $payrollTime): View|RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $employee = $profiles->employeeFor($request->user(), $organization);

        if ($employee === null) {
            return redirect()
                ->route('dashboard', $organization)
                ->with('error', __('my_hours.not_linked'));
        }

        $projects = $employee->projects()->orderBy('name')->get();

        if ($projects->isEmpty()) {
            return view('app.my-hours.index', [
                'organization' => $organization,
                'employee' => $employee,
                'projects' => $projects,
                'grid' => null,
                'selectedProject' => null,
                'year' => (int) $request->integer('year', now()->year),
                'month' => (int) $request->integer('month', now()->month),
            ]);
        }

        $projectId = $request->integer('project_id') ?: $projects->first()->id;
        $selectedProject = $projects->firstWhere('id', $projectId) ?? $projects->first();
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);

        $grid = $payrollTime->grid(
            $organization,
            $year,
            $month,
            $selectedProject->id,
            null,
        );

        $grid['rows'] = array_values(array_filter(
            $grid['rows'],
            fn (array $row): bool => $row['employee']->id === $employee->id,
        ));

        return view('app.my-hours.index', [
            'organization' => $organization,
            'employee' => $employee,
            'projects' => $projects,
            'grid' => $grid,
            'selectedProject' => $selectedProject,
            'year' => $year,
            'month' => $month,
            'years' => $payrollTime->availableYears(),
        ]);
    }

    public function upsert(
        PayrollTimeUpsertRequest $request,
        Organization $organization,
        DailyHoursService $hours,
        EmployeeProfileService $profiles,
    ): JsonResponse {
        $employee = $profiles->employeeFor($request->user(), $organization);
        abort_unless($employee !== null && $employee->id === (int) $request->integer('employee_id'), 403);

        /** @var Project $project */
        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->findOrFail($request->integer('project_id'));

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
            'message' => __('my_hours.submitted_for_approval'),
        ]);
    }
}
