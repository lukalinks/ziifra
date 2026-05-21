<?php

namespace App\Models\Concerns;

use App\Support\CurrentOrganization;
use App\Support\Workspace;
use Illuminate\Support\Str;

/**
 * Build workspace URLs for tenant models (routes under /o/{organization}/…).
 *
 * In Blade during a request, you can also use route('payroll.show', $run) because
 * SetCurrentOrganization sets url()->defaults(['organization' => $slug]).
 *
 * Outside HTTP (mail, jobs) or when defaults are missing, use $model->workspaceRoute(...).
 */
trait HasWorkspaceRoutes
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function workspaceRoute(string $name, array $parameters = []): string
    {
        $organization = $this->resolveOrganizationForRouting();

        return Workspace::route($name, $organization, array_merge([
            $this->workspaceRouteParameter() => $this,
        ], $parameters));
    }

    /**
     * Route parameter name in web.php (e.g. payrollRun, employee, leaveRequest).
     */
    protected function workspaceRouteParameter(): string
    {
        return Str::camel(class_basename($this));
    }

    protected function resolveOrganizationForRouting(): \App\Models\Organization
    {
        if ($this->relationLoaded('organization') && $this->organization !== null) {
            return $this->organization;
        }

        $organization = $this->organization()->first();

        if ($organization !== null) {
            return $organization;
        }

        return CurrentOrganization::check();
    }
}
