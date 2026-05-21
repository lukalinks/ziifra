<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, OAuthProvider $provider): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $provider->isConfigured()) {
            return redirect()->route('login')
                ->with('error', __('auth.oauth_not_configured', ['provider' => $provider->label()]));
        }

        $intent = $request->query('intent', 'login');
        if (! in_array($intent, ['login', 'register'], true)) {
            $intent = 'login';
        }

        $request->session()->put('oauth_intent', $intent);

        return Socialite::driver($provider->value)
            ->redirectUrl(\App\Support\SocialAuth::redirectUri($provider))
            ->redirect();
    }

    public function callback(Request $request, OAuthProvider $provider, SocialAuthService $socialAuth): RedirectResponse
    {
        if (! $provider->isConfigured()) {
            return redirect()->route('login')
                ->with('error', __('auth.oauth_not_configured', ['provider' => $provider->label()]));
        }

        $intent = $request->session()->pull('oauth_intent', 'login');

        try {
            $socialUser = Socialite::driver($provider->value)
                ->redirectUrl(\App\Support\SocialAuth::redirectUri($provider))
                ->user();
        } catch (\Throwable) {
            return redirect()->route($intent === 'register' ? 'register' : 'login')
                ->with('error', __('auth.oauth_failed'));
        }

        if ($socialUser->getEmail() === null) {
            return redirect()->route($intent === 'register' ? 'register' : 'login')
                ->with('error', __('auth.oauth_email_required'));
        }

        $existing = $socialAuth->findUserByProvider($provider, (string) $socialUser->getId());
        if ($existing !== null) {
            return $this->loginUser($request, $existing);
        }

        $userByEmail = $socialAuth->findUserByEmail($socialUser->getEmail());
        if ($userByEmail !== null) {
            $socialAuth->linkAccount($userByEmail, $provider, $socialUser);

            return $this->loginUser($request, $userByEmail);
        }

        if ($intent === 'login') {
            return redirect()->route('login')
                ->with('error', __('auth.oauth_no_account'))
                ->withInput(['email' => $socialUser->getEmail()]);
        }

        $request->session()->put('oauth_pending', $socialAuth->pendingRegistrationPayload($provider, $socialUser));

        return redirect()->route('register.oauth.complete');
    }

    private function loginUser(Request $request, \App\Models\User $user): RedirectResponse
    {
        Auth::login($user, true);
        $request->session()->regenerate();

        $organizations = $user->organizations()->get();

        if ($organizations->isEmpty()) {
            return redirect()->route('register')
                ->with('error', 'You are not assigned to any organization.');
        }

        if ($organizations->count() > 1) {
            return redirect()->route('organizations.select');
        }

        $organization = $organizations->first();
        $request->session()->put('current_organization_id', $organization->id);

        return redirect()->intended(Workspace::route('dashboard', $organization));
    }
}
