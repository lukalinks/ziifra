<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_company_reports(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.index', $result['organization']))
            ->assertOk()
            ->assertSee(__('reports.title'))
            ->assertSee(__('reports.scope_company'));
    }

    public function test_manager_sees_team_scoped_reports(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $managerUser = User::factory()->create();
        $result['organization']->users()->attach($managerUser->id, [
            'role' => OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $manager = Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $managerUser->id,
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'manager_id' => $manager->id,
        ]);

        $this->actingAs($managerUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.index', $result['organization']))
            ->assertOk()
            ->assertSee(__('reports.scope_team'));
    }

    public function test_employee_cannot_view_reports(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_owner_can_export_reports_csv(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@reports-export.test',
            'password123',
            'Export Co',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        Employee::factory()->forOrganization($result['organization'])->create();

        $response = $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.export', $result['organization']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', strtolower((string) $response->headers->get('content-type')));
        $this->assertStringStartsWith("\xEF\xBB\xBF", (string) $response->getContent());
        $this->assertStringContainsString(__('reports.export_column_section'), (string) $response->getContent());
    }

    public function test_owner_can_export_reports_json(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@reports-json.test',
            'password123',
            'JSON Org',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        Employee::factory()->forOrganization($result['organization'])->create();

        $response = $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.export', $result['organization'], ['format' => 'json']));

        $response->assertOk();
        $this->assertStringContainsString('application/json', strtolower((string) $response->headers->get('content-type')));
        $decoded = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('workforce', $decoded);
        $this->assertArrayHasKey('organization', $decoded);
    }

    public function test_employee_cannot_export_reports(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        Employee::factory()->forOrganization($result['organization'])->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('reports.export', $result['organization']))
            ->assertForbidden();
    }
}
