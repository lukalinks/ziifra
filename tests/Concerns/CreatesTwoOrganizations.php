<?php

namespace Tests\Concerns;

use App\Enums\OrganizationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use App\Support\CurrentOrganization;

trait CreatesTwoOrganizations
{
    /**
     * @return array{user: User, organization: Organization}
     */
    protected function createCompanyA(): array
    {
        return app(RegisterOrganizationService::class)->register(
            'Owner A',
            'owner-a@company-a.test',
            'password123',
            'Company A SHPK',
        );
    }

    /**
     * @return array{user: User, organization: Organization}
     */
    protected function createCompanyB(): array
    {
        return app(RegisterOrganizationService::class)->register(
            'Owner B',
            'owner-b@company-b.test',
            'password123',
            'Company B LLC',
        );
    }

    /**
     * @return array{employee: Employee, department: Department, position: Position, invitation: Invitation}
     */
    protected function seedCompanyBResources(Organization $companyB, User $companyBOwner): array
    {
        CurrentOrganization::set($companyB);

        $department = Department::factory()->forOrganization($companyB)->create(['name' => 'Beta Engineering']);
        $position = Position::factory()->forOrganization($companyB)->create(['title' => 'Beta Analyst']);
        $employee = Employee::factory()->forOrganization($companyB)->create([
            'first_name' => 'Beta',
            'last_name' => 'Secret',
            'email' => 'beta.secret@company-b.test',
        ]);

        $invitation = app(InvitationService::class)->send(
            $companyB,
            $companyBOwner,
            'pending@company-b.test',
            OrganizationRole::Hr,
        );

        CurrentOrganization::clear();

        return compact('employee', 'department', 'position', 'invitation');
    }

    protected function actingAsCompanyA(User $user, Organization $organization): static
    {
        return $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);
    }

}
