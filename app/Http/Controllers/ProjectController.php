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
use App\Models\ProjectTask;
use App\Services\ProjectService;
use App\Support\CurrentOrganization;
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

    public function show(Organization $organization, Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['members', 'tasks.assignee', 'createdBy']);

        return view('app.projects.show', [
            'organization' => CurrentOrganization::check(),
            'project' => $project,
            'canManage' => auth()->user()->can('update', $project),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'taskStatuses' => ProjectTaskStatus::cases(),
            'taskPriorities' => ProjectTaskPriority::cases(),
        ]);
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
