<?php

namespace Tests;

use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function useOrganizationPlan(Organization $organization, SubscriptionPlan $plan): Organization
    {
        $organization->update(['plan' => $plan->value]);

        return $organization->fresh();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function workspaceRoute(string $name, Organization $organization, array $parameters = []): string
    {
        return route($name, array_merge(['organization' => $organization->slug], $parameters));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function companySettingsPayload(Organization $organization, array $overrides = []): array
    {
        return array_merge([
            'name' => $organization->name,
            'slug' => $organization->slug,
            'country_code' => $organization->country_code ?? 'XK',
            'timezone' => $organization->timezone === 'Europe/Belgrade' ? 'Europe/Zurich' : ($organization->timezone ?? 'Europe/Zurich'),
            'currency' => $organization->currency ?? 'EUR',
            'locale' => $organization->locale ?? 'en',
            'work_week_days' => $organization->workWeekDayValues(),
            'fiscal_year_start_month' => $organization->fiscal_year_start_month ?? 1,
            'date_format' => $organization->date_format ?? 'd/m/Y',
            'observe_kosovo_holidays' => $organization->observe_kosovo_holidays ?? true,
            'default_employment_type' => $organization->default_employment_type ?? 'full_time',
            'vat_registered' => $organization->vat_registered ?? false,
            'hr_can_invite' => $organization->hr_can_invite ?? true,
        ], $overrides);
    }
}
