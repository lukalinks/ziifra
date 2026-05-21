<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\User;
use App\Services\DemoDataService;

trait UsesDemoOrganization
{
    /**
     * @return array<string, mixed>
     */
    protected function seedDemoOrganization(): array
    {
        return app(DemoDataService::class)->seed();
    }

    /**
     * @param  array<string, mixed>  $demo
     */
    protected function actingAsOwner(array $demo): static
    {
        /** @var User $owner */
        $owner = $demo['owner'];
        /** @var Organization $organization */
        $organization = $demo['organization'];

        return $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id]);
    }

    /**
     * @param  array<string, mixed>  $demo
     */
    protected function actingAsHr(array $demo): static
    {
        /** @var User $hr */
        $hr = $demo['hr'];
        /** @var Organization $organization */
        $organization = $demo['organization'];

        return $this->actingAs($hr)
            ->withSession(['current_organization_id' => $organization->id]);
    }

    /**
     * @param  array<string, mixed>  $demo
     */
    protected function actingAsEmployee(array $demo): static
    {
        /** @var User $employeeUser */
        $employeeUser = $demo['employee_user'];
        /** @var Organization $organization */
        $organization = $demo['organization'];

        return $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $organization->id]);
    }
}
