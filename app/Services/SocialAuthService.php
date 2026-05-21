<?php

namespace App\Services;

use App\Enums\OAuthProvider;
use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function findUserByProvider(OAuthProvider $provider, string $providerId): ?User
    {
        $account = OAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        return $account?->user;
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function linkAccount(User $user, OAuthProvider $provider, SocialiteUser $socialUser): OAuthAccount
    {
        return $this->linkProvider(
            $user,
            $provider,
            (string) $socialUser->getId(),
            $socialUser->getAvatar(),
        );
    }

    /**
     * @return array{name: string, email: string, provider: OAuthProvider, provider_id: string, avatar: ?string}
     */
    public function pendingRegistrationPayload(OAuthProvider $provider, SocialiteUser $socialUser): array
    {
        $email = $socialUser->getEmail();

        if ($email === null) {
            throw new \InvalidArgumentException('Email is required from the provider.');
        }

        return [
            'name' => $socialUser->getName() ?? Str::before($email, '@'),
            'email' => $email,
            'provider' => $provider->value,
            'provider_id' => (string) $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ];
    }

    /**
     * @param  array{name: string, email: string, provider: string, provider_id: string, avatar: ?string}  $pending
     * @return array{user: User, organization: \App\Models\Organization}
     */
    public function completeRegistration(array $pending, string $companyName, RegisterOrganizationService $registerService): array
    {
        return DB::transaction(function () use ($pending, $companyName, $registerService) {
            $result = $registerService->register(
                $pending['name'],
                $pending['email'],
                Str::password(32),
                $companyName,
            );

            $user = $result['user'];
            $user->forceFill(['email_verified_at' => now(), 'password' => null])->save();

            $provider = $pending['provider'] instanceof OAuthProvider
                ? $pending['provider']
                : OAuthProvider::from($pending['provider']);

            $this->linkProvider(
                $user,
                $provider,
                $pending['provider_id'],
                $pending['avatar'],
            );

            return $result;
        });
    }

    public function linkProvider(User $user, OAuthProvider $provider, string $providerId, ?string $avatar): OAuthAccount
    {
        return OAuthAccount::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_id' => $providerId,
                'avatar' => $avatar,
            ],
        );
    }
}
