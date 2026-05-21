<?php

namespace App\Services;

use App\Enums\EmployeeLoginStatus;
use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EmployeeLoginActivationService
{
    public function __construct(
        private readonly InvitationService $invitations,
        private readonly EmployeeProfileService $employeeProfiles,
    ) {}

    public function statusFor(Employee $employee): EmployeeLoginStatus
    {
        if ($employee->user_id !== null) {
            return EmployeeLoginStatus::Active;
        }

        if ($this->normalizedEmail($employee) === null) {
            return EmployeeLoginStatus::NoEmail;
        }

        if ($this->pendingInvitation($employee) !== null) {
            return EmployeeLoginStatus::PendingInvitation;
        }

        return EmployeeLoginStatus::NotActivated;
    }

    public function pendingInvitation(Employee $employee): ?Invitation
    {
        $email = $this->normalizedEmail($employee);

        if ($email === null) {
            return null;
        }

        return $employee->organization
            ->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array<string, Invitation>
     */
    public function pendingInvitationsByEmail(Collection $employees): array
    {
        $organization = $employees->first()?->organization;

        if ($organization === null) {
            return [];
        }

        $emails = $employees
            ->map(fn (Employee $employee) => $this->normalizedEmail($employee))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return [];
        }

        return $organization->invitations()
            ->whereIn('email', $emails)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy(fn (Invitation $invitation) => $invitation->email)
            ->all();
    }

    public function canActivate(Employee $employee): bool
    {
        return in_array($this->statusFor($employee), [
            EmployeeLoginStatus::NotActivated,
            EmployeeLoginStatus::PendingInvitation,
        ], true);
    }

    /**
     * Send an employee-role invitation or link an existing workspace member.
     */
    public function activate(Employee $employee, User $inviter): ?Invitation
    {
        $employee->loadMissing('organization');

        if ($employee->user_id !== null) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.login_already_active')],
            ]);
        }

        $email = $this->normalizedEmail($employee);

        if ($email === null) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.login_email_required')],
            ]);
        }

        $organization = $employee->organization;
        $existingMember = $organization->users()->where('users.email', $email)->first();

        if ($existingMember !== null) {
            $this->linkExistingMember($employee, $existingMember);

            return null;
        }

        $pending = $this->pendingInvitation($employee);

        if ($pending !== null) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.login_invitation_pending_error')],
            ]);
        }

        return $this->invitations->send(
            $organization,
            $inviter,
            $email,
            OrganizationRole::Employee,
        );
    }

    public function resend(Employee $employee, User $inviter): ?Invitation
    {
        $employee->loadMissing('organization');

        if ($employee->user_id !== null) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.login_already_active')],
            ]);
        }

        $email = $this->normalizedEmail($employee);

        if ($email === null) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.login_email_required')],
            ]);
        }

        $organization = $employee->organization;
        $existingMember = $organization->users()->where('users.email', $email)->first();

        if ($existingMember !== null) {
            $this->linkExistingMember($employee, $existingMember);

            return null;
        }

        $this->pendingInvitation($employee)?->delete();

        return $this->invitations->send(
            $organization,
            $inviter,
            $email,
            OrganizationRole::Employee,
        );
    }

    protected function linkExistingMember(Employee $employee, User $user): void
    {
        if ($employee->user_id === $user->id) {
            return;
        }

        $linked = $this->employeeProfiles->linkByEmail($user, $employee->organization);

        if ($linked === null || $linked->id !== $employee->id) {
            $employee->update(['user_id' => $user->id]);
        }
    }

    protected function normalizedEmail(Employee $employee): ?string
    {
        $email = strtolower(trim((string) $employee->email));

        return $email !== '' ? $email : null;
    }
}
