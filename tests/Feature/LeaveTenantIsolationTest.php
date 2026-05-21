<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class LeaveTenantIsolationTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_company_a_cannot_view_company_b_leave_request(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();
        $beta = $this->seedCompanyBResources($companyB['organization'], $companyB['user']);

        $leaveType = LeaveType::query()
            ->where('organization_id', $companyB['organization']->id)
            ->first();

        $request = LeaveRequest::factory()->forEmployee(
            $beta['employee'],
            $leaveType,
            $companyB['user'],
        )->create([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
        ]);

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->get($this->workspaceRoute('leave.show', $companyA['organization'], ['leaveRequest' => $request]))
            ->assertNotFound();
    }
}
