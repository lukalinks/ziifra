<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Mail\LeaveRequestReviewedMail;
use App\Mail\LeaveRequestSubmittedMail;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class LeaveNotificationService
{
    public function __construct(
        protected EmployeeProfileService $profiles,
        protected LeaveAuthorizationService $leaveAuth,
        protected InAppNotificationService $inApp,
        protected OrganizationMailService $mail,
    ) {}

    public function notifySubmitted(LeaveRequest $request): void
    {
        $request->loadMissing(['employee', 'leaveType', 'submittedBy', 'organization']);

        $recipients = $this->approverEmails($request);

        foreach ($recipients as $email) {
            $this->mail->queue($request->organization, $email, new LeaveRequestSubmittedMail($request));
        }

        $this->inApp->leaveSubmitted($request);
    }

    public function notifyReviewed(LeaveRequest $request, User $reviewer): void
    {
        $request->loadMissing(['employee', 'leaveType', 'submittedBy', 'organization', 'reviewedBy']);

        $email = $this->submitterEmail($request);

        if ($email === null) {
            return;
        }

        $this->mail->queue($request->organization, $email, new LeaveRequestReviewedMail($request, $reviewer));

        $this->inApp->leaveReviewed($request, $reviewer);
    }

    /**
     * @return Collection<int, string>
     */
    protected function approverEmails(LeaveRequest $request): Collection
    {
        $organization = $request->organization;
        $emails = collect();

        $hrUsers = $organization->users()
            ->wherePivotIn('role', [
                OrganizationRole::Owner->value,
                OrganizationRole::Admin->value,
                OrganizationRole::Hr->value,
            ])
            ->get();

        foreach ($hrUsers as $user) {
            if ($user->id !== $request->submitted_by_user_id) {
                $emails->push($user->email);
            }
        }

        $employee = $request->employee;

        if ($employee->manager_id !== null) {
            $manager = $employee->manager;

            if ($manager?->user_id !== null) {
                $managerUser = User::query()->find($manager->user_id);

                if ($managerUser !== null
                    && $managerUser->id !== $request->submitted_by_user_id
                    && $this->leaveAuth->canApprove($managerUser, $request)) {
                    $emails->push($managerUser->email);
                }
            } elseif ($manager?->email) {
                $emails->push($manager->email);
            }
        }

        return $emails->filter()->unique()->values();
    }

    protected function submitterEmail(LeaveRequest $request): ?string
    {
        if ($request->employee->email) {
            return $request->employee->email;
        }

        if ($request->employee->user_id !== null) {
            $user = User::query()->find($request->employee->user_id);

            return $user?->email;
        }

        return $request->submittedBy?->email;
    }
}
