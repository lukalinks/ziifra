@extends('layouts.auth')

@section('auth_mode', 'login')

@section('title', __('auth_pages.forgot_password.title'))

@section('aside_heading', __('auth_pages.forgot_password.aside_heading'))
@section('aside_text', __('auth_pages.forgot_password.aside_text'))

@section('content')
<div class="mb-5">
    <p class="ziifra-label">{{ __('auth_pages.forgot_password.label_account') }}</p>
    <h1 class="ziifra-display mt-2 text-2xl font-semibold text-ziifra-ink sm:text-3xl">{{ __('auth_pages.forgot_password.heading') }}</h1>
    <p class="mt-1.5 text-sm text-ziifra-muted">{{ __('auth_pages.forgot_password.subtitle') }}</p>
</div>

<div class="ziifra-auth-form-card">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
        @csrf

        <div class="ziifra-auth-field">
            <label for="email" class="ziifra-label-field">{{ __('auth_pages.forgot_password.email_label') }}</label>
            <div class="ziifra-auth-input-wrap">
                <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" inputmode="email"
                    placeholder="{{ __('auth_pages.forgot_password.email_placeholder') }}"
                    @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('email')])>
            </div>
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl !py-3">
            {{ __('auth_pages.forgot_password.submit') }}
        </button>
    </form>
</div>

<p class="mt-6 text-center text-sm text-ziifra-muted">
    {{ __('auth_pages.forgot_password.remember_password') }}
    <a href="{{ route('login') }}" class="ziifra-link font-medium">{{ __('auth_pages.forgot_password.log_in') }}</a>
</p>
@endsection
