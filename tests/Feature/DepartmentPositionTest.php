<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_department_name_in_same_org_fails_validation(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'owner@acme.test', 'password123', 'Acme SHPK');

        CurrentOrganization::set($result['organization']);
        Department::query()->create(['name' => 'Engineering']);
        CurrentOrganization::clear();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.departments.store', $result['organization']), [
                'name' => 'Engineering',
            ])
            ->assertSessionHasErrors('name');
    }
}
