@extends('layouts.auth')

@section('auth_mode', 'login')

@section('title', 'Reset password')

@section('aside_heading', 'Choose a new password')
@section('aside_text', 'Pick a strong password you have not used on ZIIFRA before.')

@section('content')
<div class="mb-5">
    <p class="ziifra-label">Account</p>
    <h1 class="ziifra-display mt-2 text-2xl font-semibold text-ziifra-ink sm:text-3xl">Reset password</h1>
    <p class="mt-1.5 text-sm text-ziifra-muted">Enter a new password for your account.</p>
</div>

<div class="ziifra-auth-form-card">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="ziifra-auth-field">
            <label for="email" class="ziifra-label-field">Email</label>
            <div class="ziifra-auth-input-wrap">
                <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                    autocomplete="username" inputmode="email" readonly
                    @class(['ziifra-input-icon bg-ziifra-cream/80', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('email')])>
            </div>
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="ziifra-auth-field">
                <label for="password" class="ziifra-label-field">New password</label>
                <div class="ziifra-auth-input-wrap">
                    <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input id="password" name="password" type="password" required autofocus autocomplete="new-password"
                        placeholder="Min. 8 characters"
                        @class(['ziifra-input-icon !pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('password')])>
                    <button type="button" class="ziifra-password-toggle" data-password-toggle="password"
                        aria-label="Show password" aria-pressed="false">
                        @include('partials.auth-password-toggle-icons')
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="ziifra-auth-field">
                <label for="password_confirmation" class="ziifra-label-field">Confirm password</label>
                <div class="ziifra-auth-input-wrap">
                    <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        placeholder="Repeat password"
                        @class(['ziifra-input-icon !pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('password_confirmation')])>
                    <button type="button" class="ziifra-password-toggle" data-password-toggle="password_confirmation"
                        aria-label="Show password" aria-pressed="false">
                        @include('partials.auth-password-toggle-icons')
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl !py-3">
            Update password
        </button>
    </form>
</div>

<p class="mt-6 text-center text-sm text-ziifra-muted">
    <a href="{{ route('login') }}" class="ziifra-link font-medium">Back to log in</a>
</p>
@endsection
