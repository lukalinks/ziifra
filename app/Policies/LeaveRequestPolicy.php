<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveAuthorizationService;
use App\Support\CurrentOrganization;

class LeaveRequestPolicy
{
    public function __construct(
        protected LeaveAuthorizationService $leaveAuth,
    ) {}

    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        $role = $user->roleIn($organization);

        if ($role === null) {
            return false;
        }

        return $role->canViewLeave()
            && ($this->leaveAuth->canViewAll($user, $organization)
                || $this->leaveAuth->canRequestOwn($user, $organization)
                || $role->canViewAllLeave());
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $leaveRequest)) {
            return false;
        }

        $organization = CurrentOrganization::check();

        if ($this->leaveAuth->canViewAll($user, $organization)) {
            return true;
        }

        if ($this->leaveAuth->ownsRequest($user, $leaveRequest)) {
            return true;
        }

        $role = $user->roleIn($organization);

        if ($role?->canViewAllLeave() && $role->canManageLeave() === false) {
            return $this->leaveAuth->managesEmployee($user, $organization, $leaveRequest->employee);
        }

        return false;
    }

    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $this->leaveAuth->canCreateForOthers($user, $organization)
            || $this->leaveAuth->canRequestOwn($user, $organization);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->belongsToCurrentOrganization($user, $leaveRequest)
            && $this->leaveAuth->canApprove($user, $leaveRequest);
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $leaveRequest) || ! $leaveRequest->isPending()) {
            return false;
        }

        $organization = CurrentOrganization::check();

        return ($user->roleIn($organization)?->canManageLeave() ?? false)
            || $leaveRequest->submitted_by_user_id === $user->id;
    }

    protected function belongsToCurrentOrganization(User $user, LeaveRequest $leaveRequest): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $leaveRequest->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
