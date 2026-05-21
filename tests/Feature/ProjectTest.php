<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_project_with_team_and_tasks(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('projects.store', $result['organization']), [
                'name' => 'Website redesign',
                'description' => 'Q2 rollout',
                'status' => ProjectStatus::Active->value,
                'employee_ids' => [$employee->id],
            ])
            ->assertRedirect();

        $project = Project::query()->first();
        $this->assertNotNull($project);
        $this->assertSame('Website redesign', $project->name);
        $this->assertTrue($project->members->contains('id', $employee->id));

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('projects.tasks.store', $result['organization'], ['project' => $project]), [
                'title' => 'Design mockups',
                'status' => ProjectTaskStatus::Todo->value,
                'priority' => 'medium',
                'assigned_employee_id' => $employee->id,
                'is_milestone' => true,
            ])
            ->assertRedirect();

        $task = ProjectTask::query()->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->is_milestone);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('projects.index', $result['organization']))
            ->assertOk()
            ->assertSee('Website redesign');

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('projects.show', $result['organization'], ['project' => $project]))
            ->assertOk()
            ->assertSee('Design mockups');
    }

    public function test_owner_can_update_task_status(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $project = Project::query()->create([
            'organization_id' => $result['organization']->id,
            'created_by_user_id' => $result['user']->id,
            'name' => 'Internal',
            'status' => ProjectStatus::Active,
        ]);

        $task = ProjectTask::query()->create([
            'organization_id' => $result['organization']->id,
            'project_id' => $project->id,
            'title' => 'Kickoff',
            'status' => ProjectTaskStatus::Todo,
            'priority' => 'low',
            'sort_order' => 1,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->put($this->workspaceRoute('projects.tasks.update', $result['organization'], [
                'project' => $project,
                'task' => $task,
            ]), [
                'status' => ProjectTaskStatus::Done->value,
            ])
            ->assertRedirect();

        $this->assertSame(ProjectTaskStatus::Done, $task->fresh()->status);
    }
}
