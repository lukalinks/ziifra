<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title') — {{ __('admin.title') }}</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ziifra-app bg-ziifra-cream">
    <div class="min-h-screen">
        <header class="border-b border-ziifra-line/80 bg-ziifra-paper/90 text-ziifra-ink backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex flex-wrap items-center gap-6">
                    <a href="{{ route('admin.dashboard') }}" class="font-semibold tracking-wide text-ziifra-ink">{{ __('admin.title') }}</a>
                    <nav class="flex flex-wrap gap-4 text-sm text-ziifra-muted">
                        <a href="{{ route('admin.dashboard') }}" class="@if(request()->routeIs('admin.dashboard')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.dashboard') }}</a>
                        <a href="{{ route('admin.organizations.index') }}" class="@if(request()->routeIs('admin.organizations.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.organizations') }}</a>
                        <a href="{{ route('admin.users.index') }}" class="@if(request()->routeIs('admin.users.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.users') }}</a>
                        <a href="{{ route('admin.audit-logs.index') }}" class="@if(request()->routeIs('admin.audit-logs.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.audit_log') }}</a>
                        <a href="{{ route('admin.languages.edit') }}" class="@if(request()->routeIs('admin.languages.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.languages') }}</a>
                        <a href="{{ route('admin.billing.edit') }}" class="@if(request()->routeIs('admin.billing.*')) text-ziifra-accent-deep @else hover:text-ziifra-ink @endif">{{ __('admin.nav.billing') }}</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <x-theme-switcher />
                    <x-locale-switcher />
                    @isset($notificationFeed)
                        <x-notification-bell :feed="$notificationFeed" />
                    @endisset
                    <span class="text-ziifra-muted">{{ auth()->user()->email }}</span>
                    <a href="{{ route('home') }}" class="text-ziifra-muted hover:text-ziifra-ink">Marketing site</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-ziifra-muted hover:text-ziifra-ink">Log out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
    <x-confirm-dialog />
</body>
</html>
