<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = User::query()->where('email', $credentials['email'])->first();

            if ($user !== null && ! $user->hasPassword()) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('auth.no_password_use_reset')]);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        $user = $request->user();
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
