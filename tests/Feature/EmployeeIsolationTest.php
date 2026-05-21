<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_in_org_a_cannot_view_org_b_employee(): void
    {
        $service = app(RegisterOrganizationService::class);

        $acme = $service->register('Owner A', 'a@acme.test', 'password123', 'Acme SHPK');
        $beta = $service->register('Owner B', 'b@beta.test', 'password123', 'Beta LLC');

        CurrentOrganization::set($beta['organization']);
        $betaEmployee = Employee::factory()->forOrganization($beta['organization'])->create([
            'first_name' => 'Beta',
            'last_name' => 'Worker',
        ]);
        CurrentOrganization::clear();

        $this->actingAs($acme['user'])
            ->withSession(['current_organization_id' => $acme['organization']->id])
            ->get($this->workspaceRoute('employees.show', $acme['organization'], ['employee' => $betaEmployee]))
            ->assertNotFound();
    }

    public function test_manager_from_another_org_is_rejected_on_create(): void
    {
        $service = app(RegisterOrganizationService::class);

        $acme = $service->register('Owner A', 'a@acme.test', 'password123', 'Acme SHPK');
        $beta = $service->register('Owner B', 'b@beta.test', 'password123', 'Beta LLC');

        CurrentOrganization::set($beta['organization']);
        $betaManager = Employee::factory()->forOrganization($beta['organization'])->create();
        CurrentOrganization::clear();

        $this->actingAs($acme['user'])
            ->withSession(['current_organization_id' => $acme['organization']->id])
            ->post($this->workspaceRoute('employees.store', $acme['organization']), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'manager_id' => $betaManager->id,
                'employment_type' => 'full_time',
                'employment_status' => 'active',
            ])
            ->assertSessionHasErrors('manager_id');
    }
}
