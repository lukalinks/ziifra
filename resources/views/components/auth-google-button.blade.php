@props(['intent' => 'login', 'compact' => false])

@php
    use App\Enums\OAuthProvider;
    $configured = OAuthProvider::Google->isConfigured();
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($configured)
        <a href="{{ route('auth.oauth.redirect', ['provider' => 'google', 'intent' => $intent]) }}"
            class="ziifra-google-btn group">
            <span class="ziifra-google-btn-icon" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
            </span>
            <span class="ziifra-google-btn-label">{{ __('auth.continue_with_google') }}</span>
        </a>
    @else
        <span class="ziifra-google-btn ziifra-google-btn-disabled cursor-not-allowed"
            title="{{ __('auth.oauth_setup_hint', ['provider' => 'Google']) }}">
            <span class="ziifra-google-btn-icon" aria-hidden="true">
                <svg class="h-5 w-5 opacity-80" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
            </span>
            <span class="ziifra-google-btn-label">{{ __('auth.continue_with_google') }}</span>
        </span>
    @endif
</div>

<div @class(['relative', $compact ? 'my-4' : 'my-8'])>
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-ziifra-line/80"></div>
    </div>
    <p @class([
        'relative mx-auto w-fit px-3 text-xs font-medium uppercase tracking-wider text-ziifra-muted',
        $compact ? 'bg-ziifra-paper' : 'bg-ziifra-paper',
    ])>
        {{ __('auth.or_email') }}
    </p>
</div>
