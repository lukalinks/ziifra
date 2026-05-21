<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_organizations_team_page(): void
    {
        $service = app(RegisterOrganizationService::class);

        $acme = $service->register('Owner A', 'a@acme.test', 'password123', 'Acme SHPK');
        $beta = $service->register('Owner B', 'b@beta.test', 'password123', 'Beta LLC');

        $this->actingAs($acme['user'])
            ->withSession(['current_organization_id' => $acme['organization']->id])
            ->get($this->workspaceRoute('team.index', $acme['organization']))
            ->assertOk();

        $this->actingAs($acme['user'])
            ->withSession(['current_organization_id' => $beta['organization']->id])
            ->get($this->workspaceRoute('team.index', $beta['organization']))
            ->assertForbidden();
    }

    public function test_non_member_cannot_view_organization(): void
    {
        $service = app(RegisterOrganizationService::class);
        $acme = $service->register('Owner A', 'a@acme.test', 'password123', 'Acme SHPK');

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->withSession(['current_organization_id' => $acme['organization']->id])
            ->get($this->workspaceRoute('dashboard', $acme['organization']))
            ->assertForbidden();
    }
}
