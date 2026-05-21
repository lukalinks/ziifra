<?php

namespace Tests\Feature;

use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_legacy_dashboard_redirects_to_workspace_url(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get('/dashboard')
            ->assertRedirect($this->workspaceRoute('dashboard', $result['organization']));
    }

    public function test_workspace_dashboard_loads_for_member(): void
    {
        $companyA = $this->createCompanyA();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('dashboard', $companyA['organization']))
            ->assertOk()
            ->assertSee('Company A SHPK');
    }

    public function test_unknown_workspace_slug_returns_not_found(): void
    {
        $companyA = $this->createCompanyA();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get('/o/does-not-exist/dashboard')
            ->assertNotFound();
    }
}
