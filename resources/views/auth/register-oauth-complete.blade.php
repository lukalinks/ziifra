@extends('layouts.auth')

@section('auth_mode', 'register')

@section('title', 'Complete registration')

@section('aside_heading', 'Almost there')
@section('aside_text', 'Name your company workspace to finish setting up ZIIFRA.')

@section('aside_points')
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        </span>
        <span>Your workspace URL is created from your company name</span>
    </li>
    <li class="ziifra-auth-trust-item">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-ziifra-accent-glow">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
        </span>
        <span>We'll email you a link to your new workspace</span>
    </li>
@endsection

@section('content')
<div class="mb-5">
    <p class="ziifra-label">One more step</p>
    <h1 class="ziifra-display mt-2 text-2xl font-semibold text-ziifra-ink sm:text-3xl">Create your workspace</h1>
    <p class="mt-1.5 text-sm text-ziifra-muted">
        Signed in with <strong>{{ $provider->label() }}</strong> as {{ $pending['email'] }}.
    </p>
</div>

<div class="ziifra-auth-form-card">
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-ziifra-line/80 bg-ziifra-cream/60 px-4 py-3">
        @if (! empty($pending['avatar']))
            <img src="{{ $pending['avatar'] }}" alt="" class="h-10 w-10 rounded-full border border-ziifra-line object-cover">
        @else
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-ziifra-accent/15 text-sm font-semibold text-ziifra-accent-deep">
                {{ strtoupper(substr($pending['name'], 0, 1)) }}
            </span>
        @endif
        <div class="min-w-0">
            <p class="truncate font-medium text-ziifra-ink">{{ $pending['name'] }}</p>
            <p class="truncate text-sm text-ziifra-muted">{{ $pending['email'] }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('register.oauth.complete.store') }}" class="space-y-4" novalidate>
        @csrf

        <div class="ziifra-auth-field">
            <label for="company_name" class="ziifra-label-field">Company name</label>
            <div class="ziifra-auth-input-wrap">
                <svg class="ziifra-auth-input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required autofocus
                    placeholder="Acme SHPK"
                    @class(['ziifra-input-icon', 'border-red-300 focus:border-red-400 focus:ring-red-200/50' => $errors->has('company_name')])>
            </div>
            @error('company_name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl !py-3">Create workspace</button>
    </form>
</div>

<p class="mt-6 text-center text-sm text-ziifra-muted">
    <a href="{{ route('register') }}" class="ziifra-link">Start over with email</a>
</p>
@endsection
