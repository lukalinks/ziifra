@extends('layouts.auth')

@section('auth_mode', 'login')

@section('title', __('auth_pages.login.title'))

@section('aside_heading', __('auth_pages.login.aside_heading'))
@section('aside_text', __('auth_pages.login.aside_text'))

@section('aside_points')
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </span>
        <span>{{ __('auth_pages.login.trust_employees') }}</span>
    </li>
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <span>{{ __('auth_pages.login.trust_leave') }}</span>
    </li>
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
        </span>
        <span>{{ __('auth_pages.login.trust_workspaces') }}</span>
    </li>
@endsection

@section('content')
<div class="ziifra-login-head">
    <span class="ziifra-login-head-icon" aria-hidden="true">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 10.5h10.5a2.25 2.25 0 002.25-2.25v-6a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 12.75v6A2.25 2.25 0 006.75 21z" />
        </svg>
    </span>
    <div>
        <p class="ziifra-label">{{ __('auth_pages.login.label_account') }}</p>
        <h1 class="ziifra-display mt-2 text-2xl font-semibold text-ziifra-ink sm:text-3xl">{{ __('auth_pages.login.heading') }}</h1>
        <p class="mt-1.5 text-sm text-ziifra-muted">{{ __('auth_pages.login.subtitle') }}</p>
    </div>
</div>

<div class="ziifra-auth-form-card">
    <x-auth-google-button intent="login" compact />

    <form method="POST" action="{{ route('login') }}" class="ziifra-login-form" novalidate>
        @csrf

        <div class="ziifra-auth-field">
            <label for="email" class="ziifra-label-field">{{ __('auth_pages.login.email_label') }}</label>
            <div class="ziifra-auth-input-wrap">
                <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" inputmode="email" spellcheck="false"
                    placeholder="{{ __('auth_pages.login.email_placeholder') }}"
                    @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('email')])>
            </div>
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="ziifra-auth-field">
            <label for="password" class="ziifra-label-field">{{ __('auth_pages.login.password_label') }}</label>
            <div class="ziifra-auth-input-wrap">
                <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input id="password" name="password" type="password" required
                    autocomplete="current-password"
                    placeholder="{{ __('auth_pages.login.password_placeholder') }}"
                    @class(['ziifra-input-icon !pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('password')])>
                <button type="button" class="ziifra-password-toggle" data-password-toggle="password"
                    aria-label="{{ __('auth_pages.login.show_password') }}" aria-pressed="false">
                    @include('partials.auth-password-toggle-icons')
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="ziifra-login-options">
            <label class="ziifra-login-remember">
                <input type="checkbox" name="remember" @checked(old('remember'))
                    class="h-4 w-4 rounded border-ziifra-line bg-ziifra-paper text-ziifra-accent-deep focus:ring-ziifra-accent/25">
                {{ __('auth_pages.login.remember_me') }}
            </label>
            <a href="{{ route('password.request') }}" class="ziifra-login-forgot">
                {{ __('auth_pages.login.forgot_password') }}
            </a>
        </div>

        <x-form.submit :label="__('auth_pages.login.submit')" />
    </form>
</div>

<p class="ziifra-login-secondary mt-6 text-center text-sm text-ziifra-muted">
    {{ __('auth_pages.login.new_to') }}
    <a href="{{ route('register') }}" class="ziifra-link font-medium">{{ __('auth_pages.login.start_trial') }}</a>
</p>
<p class="ziifra-login-legal mt-2 text-center text-xs text-ziifra-muted">
    <a href="{{ route('terms') }}" class="underline hover:text-ziifra-accent-deep">{{ __('auth_pages.login.terms') }}</a>
    ·
    <a href="{{ route('privacy') }}" class="underline hover:text-ziifra-accent-deep">{{ __('auth_pages.login.privacy') }}</a>
</p>
@endsection
