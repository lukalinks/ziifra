<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Mail\TeamInvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function send(
        Organization $organization,
        User $inviter,
        string $email,
        OrganizationRole $role,
    ): Invitation {
        $email = strtolower(trim($email));

        if ($organization->users()->where('users.email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This person is already a team member.'],
            ]);
        }

        $pending = $organization->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'email' => ['An invitation has already been sent to this email.'],
            ]);
        }

        $invitation = $organization->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token' => Invitation::generateToken(),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Deliver synchronously: Mail::queue()/Mail::send() both defer ShouldQueue mailables,
        // which requires a worker when QUEUE_CONNECTION is database/redis.
        Mail::to($email)->sendNow(new TeamInvitationMail($invitation));

        return $invitation;
    }

    /**
     * @return array{user: User, organization: Organization}
     */
    public function accept(Invitation $invitation, ?string $name, ?string $password): array
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is no longer valid.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::query()->where('email', $invitation->email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name ?? explode('@', $invitation->email)[0],
                    'email' => $invitation->email,
                    'password' => $password,
                ]);
            } elseif ($password !== null) {
                $user->update(['password' => $password]);
            }

            if ($user->belongsToOrganization($invitation->organization)) {
                throw ValidationException::withMessages([
                    'token' => ['You are already a member of this organization.'],
                ]);
            }

            $invitation->organization->users()->attach($user->id, [
                'role' => $invitation->role->value,
                'joined_at' => now(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            app(EmployeeProfileService::class)->linkAfterInvitation(
                $user,
                $invitation->organization,
                $invitation->role,
            );

            return [
                'user' => $user,
                'organization' => $invitation->organization,
            ];
        });
    }
}
