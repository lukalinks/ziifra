<?php

namespace App\Policies;

use App\Models\ExpenseClaim;
use App\Models\User;
use App\Services\ExpenseAuthorizationService;
use App\Support\CurrentOrganization;

class ExpenseClaimPolicy
{
    public function __construct(
        protected ExpenseAuthorizationService $expenseAuth,
    ) {}

    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $this->expenseAuth->canViewAll($user, $organization)
            || $this->expenseAuth->canSubmitOwn($user, $organization)
            || $user->roleIn($organization)?->usesTeamDashboard() === true;
    }

    public function view(User $user, ExpenseClaim $claim): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $claim)) {
            return false;
        }

        $organization = $claim->organization;

        if ($this->expenseAuth->canViewAll($user, $organization)) {
            return true;
        }

        if ($this->expenseAuth->ownsClaim($user, $claim)) {
            return true;
        }

        if ($user->roleIn($organization)?->usesTeamDashboard()) {
            return $this->expenseAuth->managesEmployee($user, $organization, $claim->employee);
        }

        return false;
    }

    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $this->expenseAuth->canCreateForOthers($user, $organization)
            || $this->expenseAuth->canSubmitOwn($user, $organization);
    }

    public function approve(User $user, ExpenseClaim $claim): bool
    {
        return $this->view($user, $claim) && $this->expenseAuth->canApprove($user, $claim);
    }

    public function cancel(User $user, ExpenseClaim $claim): bool
    {
        if (! $claim->isPending()) {
            return false;
        }

        return $this->expenseAuth->ownsClaim($user, $claim)
            || $this->expenseAuth->canViewAll($user, $claim->organization);
    }

    protected function belongsToCurrentOrganization(User $user, ExpenseClaim $claim): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $claim->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
