<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeAuthorizationService;
use App\Support\CurrentOrganization;

class TimeEntryPolicy
{
    public function __construct(
        protected TimeAuthorizationService $timeAuth,
    ) {}

    public function viewAny(User $user): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        return $this->timeAuth->canViewAll($user, $organization)
            || $this->timeAuth->canViewTeam($user, $organization)
            || $this->timeAuth->canClockOwn($user, $organization);
    }

    public function view(User $user, TimeEntry $entry): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $entry)) {
            return false;
        }

        $organization = $entry->organization;

        if ($this->timeAuth->canViewAll($user, $organization)) {
            return true;
        }

        if ($this->timeAuth->ownsEntry($user, $entry)) {
            return true;
        }

        if ($this->timeAuth->canViewTeam($user, $organization)) {
            return $this->timeAuth->canClockFor($user, $organization, $entry->employee);
        }

        return false;
    }

    public function clock(User $user, ?Employee $employee = null): bool
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return false;
        }

        if ($employee === null) {
            if ($this->timeAuth->canViewAll($user, $organization)) {
                return true;
            }

            return $this->timeAuth->canClockOwn($user, $organization)
                || $this->timeAuth->canViewTeam($user, $organization);
        }

        return $this->timeAuth->canClockFor($user, $organization, $employee);
    }

    public function create(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $this->timeAuth->canManageEntries($user, $organization);
    }

    public function update(User $user, TimeEntry $entry): bool
    {
        if (! $this->belongsToCurrentOrganization($user, $entry)) {
            return false;
        }

        return $this->timeAuth->canManageEntries($user, $entry->organization);
    }

    public function delete(User $user, TimeEntry $entry): bool
    {
        return $this->update($user, $entry);
    }

    protected function belongsToCurrentOrganization(User $user, TimeEntry $entry): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $entry->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
