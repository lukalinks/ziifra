<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskPriority;
use App\Enums\ProjectTaskStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use App\Enums\ProjectDocumentCategory;
use App\Services\DailyHoursService;
use App\Services\ProjectService;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query()
            ->withCount('tasks')
            ->with(['members'])
            ->orderByDesc('updated_at');

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, array_column(ProjectStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('app.projects.index', [
            'organization' => CurrentOrganization::check(),
            'projects' => $query->paginate(15)->withQueryString(),
            'statuses' => ProjectStatus::cases(),
            'canManage' => $request->user()->can('create', Project::class),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('app.projects.create', [
            'organization' => CurrentOrganization::check(),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'statuses' => ProjectStatus::cases(),
        ]);
    }

    public function store(StoreProjectRequest $request, ProjectService $projects): RedirectResponse
    {
        $project = $projects->create($request->validated(), $request->user());

        return redirect()
            ->to($project->workspaceRoute('projects.show'))
            ->with('status', __('projects.created'));
    }

    public function show(Organization $organization, Project $project, Request $request, DailyHoursService $hours): View
    {
        $this->authorize('view', $project);

        $project->load(['members', 'tasks.assignee', 'createdBy', 'documents.uploadedBy']);

        $month = Carbon::parse($request->string('month')->toString() ?: now()->format('Y-m'))->startOfMonth();
        $hoursGrid = $hours->gridForProject($project, $month, $request->string('search')->trim()->toString() ?: null);
        $tab = $request->string('tab')->toString() ?: 'hours';
        $chartYear = (int) $request->integer('chart_year', now()->year);
        $chartMonth = $request->has('chart_month') ? (int) $request->integer('chart_month') : null;

        return view('app.projects.show', [
            'organization' => CurrentOrganization::check(),
            'project' => $project,
            'canManage' => auth()->user()->can('update', $project),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'taskStatuses' => ProjectTaskStatus::cases(),
            'taskPriorities' => ProjectTaskPriority::cases(),
            'hoursGrid' => $hoursGrid,
            'selectedMonth' => $month->format('Y-m'),
            'search' => $request->string('search')->trim()->toString(),
            'tab' => in_array($tab, ['hours', 'tasks', 'team', 'documents'], true) ? $tab : 'hours',
            'documentCategories' => ProjectDocumentCategory::cases(),
            'hoursChart' => $this->hoursChartData($project, $chartYear, $chartMonth),
            'chartYear' => $chartYear,
            'chartMonth' => $chartMonth,
        ]);
    }

    public function hoursChart(Organization $organization, Project $project, Request $request): View
    {
        $this->authorize('view', $project);

        $chartYear = (int) $request->integer('year', now()->year);
        $chartMonth = $request->has('month') ? (int) $request->integer('month') : null;

        return view('app.projects._hours-chart', [
            'project' => $project,
            'hoursChart' => $this->hoursChartData($project, $chartYear, $chartMonth),
            'chartYear' => $chartYear,
            'chartMonth' => $chartMonth,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function hoursChartData(Project $project, int $year, ?int $chartMonth): array
    {
        $query = \App\Models\DailyHoursEntry::query()
            ->where('project_id', $project->id)
            ->with('employee');

        if ($chartMonth) {
            $start = Carbon::create($year, $chartMonth, 1)->startOfMonth();
            $query->whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        } else {
            $query->whereYear('work_date', $year);
        }

        $entries = $query->get();
        $byDate = $entries->groupBy(fn ($e) => $e->work_date->format('Y-m-d'))->sortKeys();
        $byEmployee = $entries->groupBy('employee_id');

        $byDateDetail = $byDate->map(fn ($group, $date) => [
            'date' => $date,
            'hours' => (float) $group->sum('hours'),
            'people' => $group
                ->filter(fn ($e) => (float) $e->hours > 0)
                ->groupBy('employee_id')
                ->map(fn ($g) => [
                    'name' => $g->first()->employee?->fullName() ?? '—',
                    'hours' => (float) $g->sum('hours'),
                ])
                ->values(),
        ])->values();

        $maxDay = (float) ($byDate->map(fn ($group) => (float) $group->sum('hours'))->max() ?? 0);

        return [
            'total_hours' => (float) $entries->sum('hours'),
            'by_date' => $byDate->map(fn ($group) => (float) $group->sum('hours')),
            'by_date_detail' => $byDateDetail,
            'max_day_hours' => $maxDay,
            'by_employee' => $byEmployee->map(fn ($group) => [
                'name' => $group->first()->employee?->fullName() ?? '—',
                'hours' => (float) $group->sum('hours'),
            ])->sortByDesc('hours')->values(),
        ];
    }

    public function edit(Organization $organization, Project $project): View
    {
        $this->authorize('update', $project);

        $project->load('members');

        return view('app.projects.edit', [
            'organization' => CurrentOrganization::check(),
            'project' => $project,
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'statuses' => ProjectStatus::cases(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Organization $organization, Project $project, ProjectService $projects): RedirectResponse
    {
        $this->authorize('update', $project);

        $projects->update($project, $request->validated());

        return redirect()
            ->to($project->workspaceRoute('projects.show'))
            ->with('status', __('projects.updated'));
    }

    public function destroy(Organization $organization, Project $project, ProjectService $projects): RedirectResponse
    {
        $this->authorize('delete', $project);

        $projects->delete($project);

        return redirect()
            ->route('projects.index')
            ->with('status', __('projects.deleted'));
    }

    public function storeMember(
        Request $request,
        Organization $organization,
        Project $project,
    ): RedirectResponse {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $project->members()->syncWithoutDetaching([$validated['employee_id']]);

        return redirect()
            ->to($project->workspaceRoute('projects.show', ['tab' => 'team']))
            ->with('status', __('projects.member_added'));
    }

    public function destroyMember(
        Organization $organization,
        Project $project,
        Employee $employee,
    ): RedirectResponse {
        $this->authorize('update', $project);

        $project->members()->detach($employee->id);

        return redirect()
            ->to($project->workspaceRoute('projects.show', ['tab' => 'team']))
            ->with('status', __('projects.member_removed'));
    }

    public function storeTask(
        StoreProjectTaskRequest $request,
        Organization $organization,
        Project $project,
        ProjectService $projects,
    ): RedirectResponse {
        $projects->addTask($project, $request->validated());

        return redirect()
            ->to($project->workspaceRoute('projects.show'))
            ->with('status', __('projects.task_added'));
    }

    public function updateTask(
        Request $request,
        Organization $organization,
        Project $project,
        ProjectTask $task,
    ): RedirectResponse {
        $this->authorize('update', $project);

        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(ProjectTaskStatus::cases(), 'value'))],
        ]);

        $task->update(['status' => $validated['status']]);

        return redirect()
            ->to($project->workspaceRoute('projects.show'))
            ->with('status', __('projects.task_updated'));
    }

    public function destroyTask(
        Organization $organization,
        Project $project,
        ProjectTask $task,
    ): RedirectResponse {
        $this->authorize('update', $project);

        abort_unless($task->project_id === $project->id, 404);

        $task->delete();

        return redirect()
            ->to($project->workspaceRoute('projects.show'))
            ->with('status', __('projects.task_deleted'));
    }
}
