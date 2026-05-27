<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title') — {{ __('admin.title') }}</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ziifra-app bg-ziifra-cream">
    <div class="min-h-screen min-h-[100dvh]">
        <header class="sticky top-0 z-30 border-b border-ziifra-line/80 bg-ziifra-paper/90 text-ziifra-ink backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-3 py-3 sm:px-6 sm:py-4">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button type="button" class="ziifra-mobile-menu-btn md:hidden" data-admin-nav-open aria-label="{{ __('navigation.open_menu') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="truncate font-semibold tracking-wide text-ziifra-ink">{{ __('admin.title') }}</a>
                    <nav class="hidden flex-wrap gap-4 text-sm text-ziifra-muted md:flex">
                        <a href="{{ route('admin.dashboard') }}" class="@if(request()->routeIs('admin.dashboard')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.dashboard') }}</a>
                        <a href="{{ route('admin.organizations.index') }}" class="@if(request()->routeIs('admin.organizations.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.organizations') }}</a>
                        <a href="{{ route('admin.users.index') }}" class="@if(request()->routeIs('admin.users.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.users') }}</a>
                        <a href="{{ route('admin.audit-logs.index') }}" class="@if(request()->routeIs('admin.audit-logs.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.audit_log') }}</a>
                        <a href="{{ route('admin.languages.edit') }}" class="@if(request()->routeIs('admin.languages.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.languages') }}</a>
                        <a href="{{ route('admin.billing.edit') }}" class="@if(request()->routeIs('admin.billing.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.billing') }}</a>
                    </nav>
                </div>
                <div class="flex shrink-0 items-center gap-2 text-sm sm:gap-4">
                    <x-theme-switcher />
                    <x-locale-switcher />
                    @isset($notificationFeed)
                        <x-notification-bell :feed="$notificationFeed" />
                    @endisset
                    <span class="hidden max-w-[10rem] truncate text-ziifra-muted sm:inline">{{ auth()->user()->email }}</span>
                    <a href="{{ route('home') }}" class="hidden text-ziifra-muted hover:text-ziifra-ink sm:inline">Marketing site</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-ziifra-muted hover:text-ziifra-ink">{{ __('common.log_out') }}</button>
                    </form>
                </div>
            </div>
        </header>

        <div id="ziifra-admin-mobile-nav" class="ziifra-admin-mobile-nav" aria-hidden="true" inert>
            <div class="ziifra-mobile-nav-backdrop" data-admin-nav-close tabindex="-1" aria-hidden="true"></div>
            <aside class="ziifra-admin-mobile-nav-panel" role="dialog" aria-modal="true" aria-label="{{ __('navigation.mobile_nav') }}">
                <div class="flex items-center justify-between border-b border-ziifra-line/80 px-4 py-4">
                    <p class="font-semibold text-ziifra-ink">{{ __('admin.title') }}</p>
                    <button type="button" class="ziifra-mobile-menu-btn" data-admin-nav-close aria-label="{{ __('navigation.close_menu') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="flex flex-col gap-1 p-3 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="ziifra-nav-link @if(request()->routeIs('admin.dashboard')) ziifra-nav-link-active @endif">{{ __('admin.nav.dashboard') }}</a>
                    <a href="{{ route('admin.organizations.index') }}" class="ziifra-nav-link @if(request()->routeIs('admin.organizations.*')) ziifra-nav-link-active @endif">{{ __('admin.nav.organizations') }}</a>
                    <a href="{{ route('admin.users.index') }}" class="ziifra-nav-link @if(request()->routeIs('admin.users.*')) ziifra-nav-link-active @endif">{{ __('admin.nav.users') }}</a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="ziifra-nav-link @if(request()->routeIs('admin.audit-logs.*')) ziifra-nav-link-active @endif">{{ __('admin.nav.audit_log') }}</a>
                    <a href="{{ route('admin.languages.edit') }}" class="ziifra-nav-link @if(request()->routeIs('admin.languages.*')) ziifra-nav-link-active @endif">{{ __('admin.nav.languages') }}</a>
                    <a href="{{ route('admin.billing.edit') }}" class="ziifra-nav-link @if(request()->routeIs('admin.billing.*')) ziifra-nav-link-active @endif">{{ __('admin.nav.billing') }}</a>
                    <a href="{{ route('home') }}" class="ziifra-nav-link">Marketing site</a>
                </nav>
            </aside>
        </div>

        <main class="mx-auto min-w-0 max-w-6xl px-3 py-6 sm:px-6 sm:py-8">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
    <x-confirm-dialog />
</body>
</html>
