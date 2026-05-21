@extends('layouts.auth')

@section('auth_mode', 'register')

@section('title', __('auth_pages.register.title'))

@section('aside_heading', __('auth_pages.register.aside_heading'))
@section('aside_text', __('auth_pages.register.aside_text'))

@section('aside_points')
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        </span>
        <span>{{ __('auth_pages.register.trust_workspace') }}</span>
    </li>
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </span>
        <span>{{ __('auth_pages.register.trust_trial') }}</span>
    </li>
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
        </span>
        <span>{{ __('auth_pages.register.trust_welcome_email') }}</span>
    </li>
@endsection

@section('content')
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <p class="ziifra-label">{{ __('auth_pages.register.label_get_started') }}</p>
        <h1 class="ziifra-display mt-2 text-2xl font-semibold text-ziifra-ink sm:text-3xl">{{ __('auth_pages.register.heading') }}</h1>
        <p class="mt-1.5 text-sm text-ziifra-muted">{{ __('auth_pages.register.subtitle') }}</p>
    </div>
    <span class="rounded-full border border-ziifra-accent/30 bg-ziifra-accent/10 px-3 py-1 font-mono text-[0.65rem] font-medium uppercase tracking-wider text-ziifra-accent-deep">
        {{ __('auth_pages.register.badge_trial') }}
    </span>
</div>

<div class="ziifra-auth-form-card">
    <x-auth-google-button intent="register" compact />

    <form method="POST" action="{{ route('register') }}" class="space-y-6" novalidate>
        @csrf

        <fieldset class="space-y-4">
            <legend class="ziifra-auth-section-title">{{ __('auth_pages.register.section_workspace') }}</legend>

            <div class="ziifra-auth-field">
                <label for="company_name" class="ziifra-label-field">{{ __('auth_pages.register.company_name') }}</label>
                <div class="ziifra-auth-input-wrap">
                    <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required autofocus
                        placeholder="{{ __('auth_pages.register.company_placeholder') }}"
                        @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('company_name')])>
                </div>
                @error('company_name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="ziifra-auth-section-title">{{ __('auth_pages.register.section_profile') }}</legend>

            <div class="ziifra-auth-field">
                <label for="name" class="ziifra-label-field">{{ __('auth_pages.register.your_name') }}</label>
                <div class="ziifra-auth-input-wrap">
                    <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                        placeholder="{{ __('auth_pages.register.name_placeholder') }}"
                        @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('name')])>
                </div>
                @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="ziifra-auth-field">
                <label for="email" class="ziifra-label-field">{{ __('auth_pages.register.work_email') }}</label>
                <div class="ziifra-auth-input-wrap">
                    <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email"
                        placeholder="{{ __('auth_pages.login.email_placeholder') }}"
                        @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('email')])>
                </div>
                @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="ziifra-auth-section-title">{{ __('auth_pages.register.section_security') }}</legend>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="ziifra-auth-field">
                    <label for="password" class="ziifra-label-field">{{ __('auth_pages.register.password') }}</label>
                    <div class="ziifra-auth-input-wrap">
                        <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            placeholder="{{ __('auth_pages.register.password_placeholder') }}"
                            @class(['ziifra-input-icon !pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('password')])>
                        <button type="button" class="ziifra-password-toggle" data-password-toggle="password"
                            aria-label="{{ __('auth_pages.login.show_password') }}" aria-pressed="false">
                            @include('partials.auth-password-toggle-icons')
                        </button>
                    </div>
                    @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="ziifra-auth-field">
                    <label for="password_confirmation" class="ziifra-label-field">{{ __('auth_pages.register.confirm_password') }}</label>
                    <div class="ziifra-auth-input-wrap">
                        <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            placeholder="{{ __('auth_pages.register.confirm_placeholder') }}"
                            @class(['ziifra-input-icon !pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('password_confirmation')])>
                        <button type="button" class="ziifra-password-toggle" data-password-toggle="password_confirmation"
                            aria-label="{{ __('auth_pages.login.show_password') }}" aria-pressed="false">
                            @include('partials.auth-password-toggle-icons')
                        </button>
                    </div>
                    @error('password_confirmation')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>

        <p class="text-xs text-ziifra-muted">
            {{ __('auth_pages.register.terms_agree') }}
            <a href="{{ route('terms') }}" class="underline hover:text-ziifra-accent-deep">{{ __('auth_pages.register.terms') }}</a>
            {{ __('auth_pages.register.and') }}
            <a href="{{ route('privacy') }}" class="underline hover:text-ziifra-accent-deep">{{ __('auth_pages.register.privacy_policy') }}</a>.
            {{ __('auth_pages.register.welcome_email_note') }}
        </p>

        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl !py-3">
            {{ __('auth_pages.register.submit') }}
        </button>
    </form>
</div>

<p class="mt-6 text-center text-sm text-ziifra-muted">
    {{ __('auth_pages.register.already_have') }}
    <a href="{{ route('login') }}" class="ziifra-link font-medium">{{ __('auth_pages.register.log_in') }}</a>
</p>
@endsection
