<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ExpenseClaimReviewedNotification;
use App\Notifications\ExpenseClaimSubmittedNotification;
use App\Notifications\LeaveRequestReviewedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use Illuminate\Support\Collection;

class InAppNotificationService
{
    public function __construct(
        protected LeaveAuthorizationService $leaveAuth,
        protected ExpenseAuthorizationService $expenseAuth,
    ) {}

    public function leaveSubmitted(LeaveRequest $request): void
    {
        $request->loadMissing(['employee', 'organization']);

        foreach ($this->leaveApprovers($request) as $user) {
            $user->notify(new LeaveRequestSubmittedNotification($request));
        }
    }

    public function leaveReviewed(LeaveRequest $request, User $reviewer): void
    {
        $recipient = $this->employeeUser($request);

        if ($recipient === null || $recipient->id === $reviewer->id) {
            return;
        }

        $recipient->notify(new LeaveRequestReviewedNotification($request, $reviewer));
    }

    public function expenseSubmitted(ExpenseClaim $claim): void
    {
        $claim->loadMissing(['employee', 'organization', 'submittedBy']);

        foreach ($this->expenseApprovers($claim) as $user) {
            $user->notify(new ExpenseClaimSubmittedNotification($claim));
        }
    }

    public function expenseReviewed(ExpenseClaim $claim, User $reviewer): void
    {
        $recipient = $this->claimEmployeeUser($claim);

        if ($recipient === null || $recipient->id === $reviewer->id) {
            return;
        }

        $recipient->notify(new ExpenseClaimReviewedNotification($claim, $reviewer));
    }

    /**
     * @return Collection<int, User>
     */
    protected function leaveApprovers(LeaveRequest $request): Collection
    {
        $organization = $request->organization;
        $users = collect();

        $organization->users()
            ->wherePivotIn('role', [
                OrganizationRole::Owner->value,
                OrganizationRole::Admin->value,
                OrganizationRole::Hr->value,
            ])
            ->get()
            ->each(function (User $user) use ($request, $users): void {
                if ($user->id !== $request->submitted_by_user_id) {
                    $users->push($user);
                }
            });

        $employee = $request->employee;

        if ($employee->manager_id !== null) {
            $manager = $employee->manager;

            if ($manager?->user_id !== null) {
                $managerUser = User::query()->find($manager->user_id);

                if ($managerUser !== null
                    && $managerUser->id !== $request->submitted_by_user_id
                    && $this->leaveAuth->canApprove($managerUser, $request)
                    && ! $users->contains('id', $managerUser->id)) {
                    $users->push($managerUser);
                }
            }
        }

        return $users->values();
    }

    /**
     * @return Collection<int, User>
     */
    protected function expenseApprovers(ExpenseClaim $claim): Collection
    {
        $organization = $claim->organization;

        return $organization->users()
            ->wherePivotIn('role', [
                OrganizationRole::Owner->value,
                OrganizationRole::Admin->value,
                OrganizationRole::Hr->value,
            ])
            ->get()
            ->filter(fn (User $user) => $user->id !== $claim->submitted_by_user_id
                && $this->expenseAuth->canApprove($user, $claim))
            ->values();
    }

    protected function employeeUser(LeaveRequest $request): ?User
    {
        $employee = $request->employee;

        if ($employee->user_id !== null) {
            return User::query()->find($employee->user_id);
        }

        if ($employee->email) {
            return User::query()->where('email', $employee->email)->first();
        }

        return null;
    }

    protected function claimEmployeeUser(ExpenseClaim $claim): ?User
    {
        $employee = $claim->employee;

        if ($employee->user_id !== null) {
            return User::query()->find($employee->user_id);
        }

        if ($employee->email) {
            return User::query()->where('email', $employee->email)->first();
        }

        return null;
    }
}
