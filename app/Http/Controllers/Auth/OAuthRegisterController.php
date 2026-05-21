<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Services\RegisterOrganizationService;
use App\Services\SocialAuthService;
use App\Support\Workspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OAuthRegisterController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('oauth_pending')) {
            return redirect()->route('register')
                ->with('error', __('auth.oauth_session_expired'));
        }

        $pending = $request->session()->get('oauth_pending');

        return view('auth.register-oauth-complete', [
            'pending' => $pending,
            'provider' => $pending['provider'] instanceof OAuthProvider
                ? $pending['provider']
                : OAuthProvider::from($pending['provider']),
        ]);
    }

    public function store(
        Request $request,
        SocialAuthService $socialAuth,
        RegisterOrganizationService $registerService,
    ): RedirectResponse {
        $pending = $request->session()->get('oauth_pending');

        if ($pending === null) {
            return redirect()->route('register')
                ->with('error', __('auth.oauth_session_expired'));
        }

        if ($pending['provider'] instanceof OAuthProvider === false) {
            $pending['provider'] = OAuthProvider::from($pending['provider']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        if ($socialAuth->findUserByEmail($pending['email']) !== null) {
            return redirect()->route('login')
                ->with('error', __('auth.oauth_account_exists'))
                ->withInput(['email' => $pending['email']]);
        }

        $result = $socialAuth->completeRegistration($pending, $validated['company_name'], $registerService);

        event(new Registered($result['user']));

        $request->session()->forget('oauth_pending');
        Auth::login($result['user'], true);
        $request->session()->regenerate();
        $request->session()->put('current_organization_id', $result['organization']->id);

        return Workspace::redirect('dashboard', $result['organization']);
    }
}
