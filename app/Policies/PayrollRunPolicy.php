<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManagePayroll($user);
    }

    public function view(User $user, PayrollRun $run): bool
    {
        return $this->belongsToCurrentOrganization($user, $run)
            && $this->canManagePayroll($user);
    }

    public function create(User $user): bool
    {
        return $this->canManagePayroll($user);
    }

    public function update(User $user, PayrollRun $run): bool
    {
        return $this->view($user, $run) && $run->isDraft();
    }

    public function lock(User $user, PayrollRun $run): bool
    {
        return $this->update($user, $run);
    }

    public function sendPayslipEmails(User $user, PayrollRun $run): bool
    {
        return $this->view($user, $run);
    }

    protected function canManagePayroll(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        $role = $user->roleIn($organization);

        if (! ($role?->canManageEmployees() ?? false)) {
            return false;
        }

        return app(OrganizationBillingService::class)->hasPayroll($organization);
    }

    protected function belongsToCurrentOrganization(User $user, PayrollRun $run): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $run->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
