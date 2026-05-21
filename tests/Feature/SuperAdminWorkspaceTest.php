<?php

namespace Tests\Feature;

use App\Services\DemoDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    protected function workspaceRoutes(): array
    {
        return [
            'dashboard',
            'employees.index',
            'documents.index',
            'leave.index',
            'payroll.index',
            'invoices.index',
            'expenses.index',
            'projects.index',
            'time.index',
            'reports.index',
            'chat.index',
            'settings.index',
            'team.index',
            'leave.calendar',
        ];
    }

    public function test_impersonated_owner_can_access_all_workspace_modules(): void
    {
        $demo = app(DemoDataService::class)->seed();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];
        $owner = $demo['owner'];

        $this->actingAs($admin)
            ->post(route('admin.organizations.impersonate', $organization))
            ->assertRedirect($this->workspaceRoute('dashboard', $organization));

        $session = [
            'impersonator_id' => $admin->id,
            'current_organization_id' => $organization->id,
        ];

        foreach ($this->workspaceRoutes() as $routeName) {
            $this->actingAs($owner)
                ->withSession($session)
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Failed asserting {$routeName} is accessible while impersonating.");
        }
    }

    public function test_super_admin_panel_remains_accessible_while_not_impersonating(): void
    {
        $demo = app(DemoDataService::class)->seed();
        $admin = $demo['super_admin'];

        foreach (['admin.dashboard', 'admin.organizations.index', 'admin.users.index', 'admin.audit-logs.index'] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk("Failed asserting {$route} is accessible.");
        }
    }

    public function test_stop_impersonation_restores_super_admin_session(): void
    {
        $demo = app(DemoDataService::class)->seed();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];

        $this->actingAs($admin)
            ->post(route('admin.organizations.impersonate', $organization));

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }
}
