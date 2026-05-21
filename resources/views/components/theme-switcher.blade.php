@php
    $resolved = 'light';
@endphp

<div class="ziifra-theme-switcher inline-flex items-center rounded-lg border border-ziifra-line/80 bg-ziifra-paper p-0.5 shadow-sm"
    data-theme-switcher
    role="group"
    aria-label="{{ __('themes.switch_label') }}">
    <button type="button"
        data-theme-option="light"
        aria-pressed="false"
        title="{{ __('themes.light') }}"
        class="ziifra-theme-option inline-flex h-8 w-8 items-center justify-center rounded-md text-ziifra-muted transition hover:text-ziifra-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-ziifra-accent/40">
        <span class="sr-only">{{ __('themes.light') }}</span>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
    </button>
    <button type="button"
        data-theme-option="dark"
        aria-pressed="false"
        title="{{ __('themes.dark') }}"
        class="ziifra-theme-option inline-flex h-8 w-8 items-center justify-center rounded-md text-ziifra-muted transition hover:text-ziifra-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-ziifra-accent/40">
        <span class="sr-only">{{ __('themes.dark') }}</span>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </button>
</div>
