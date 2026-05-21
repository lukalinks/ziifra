<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskPriority;
use App\Enums\ProjectTaskStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Support\CurrentOrganization;

class ProjectService
{
    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     status: string,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     budget?: float|string|null,
     *     employee_ids?: list<int>,
     * }  $data
     */
    public function create(array $data, User $user): Project
    {
        $organization = CurrentOrganization::check();

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'currency' => $organization->currency ?? 'EUR',
        ]);

        $this->syncMembers($project, $data['employee_ids'] ?? []);

        return $project->load(['members', 'tasks.assignee']);
    }

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     status: string,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     budget?: float|string|null,
     *     employee_ids?: list<int>,
     * }  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? null,
        ]);

        if (array_key_exists('employee_ids', $data)) {
            $this->syncMembers($project, $data['employee_ids'] ?? []);
        }

        return $project->fresh(['members', 'tasks.assignee']);
    }

    /**
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     status: string,
     *     priority: string,
     *     assigned_employee_id?: int|null,
     *     is_milestone?: bool,
     *     due_date?: string|null,
     * }  $data
     */
    public function addTask(Project $project, array $data): ProjectTask
    {
        $maxOrder = $project->tasks()->max('sort_order') ?? 0;

        return ProjectTask::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? ProjectTaskStatus::Todo->value,
            'priority' => $data['priority'] ?? ProjectTaskPriority::Medium->value,
            'assigned_employee_id' => $data['assigned_employee_id'] ?? null,
            'is_milestone' => $data['is_milestone'] ?? false,
            'due_date' => $data['due_date'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    /**
     * @param  list<int>  $employeeIds
     */
    protected function syncMembers(Project $project, array $employeeIds): void
    {
        $validIds = Employee::query()
            ->whereIn('id', $employeeIds)
            ->pluck('id')
            ->all();

        $project->members()->sync($validIds);
    }
}
