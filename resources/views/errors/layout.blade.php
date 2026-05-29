<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @include('partials.social-meta')
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ziifra-app flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
        <p class="font-mono text-sm uppercase tracking-[0.24em] text-ziifra-accent-deep">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-semibold text-ziifra-ink">@yield('message')</h1>
        <p class="mt-3 text-sm text-ziifra-muted">@yield('detail')</p>
        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <x-theme-switcher />
            <a href="{{ url('/') }}" class="ziifra-btn-app">{{ __('auth_pages.errors.back_to_home') }}</a>
            @auth
                <a href="{{ route('organizations.select') }}" class="ziifra-btn-app-outline">{{ __('auth_pages.errors.select_workspace') }}</a>
            @else
                <a href="{{ route('login') }}" class="ziifra-btn-app-outline">{{ __('auth_pages.errors.log_in') }}</a>
            @endauth
        </div>
    </div>
</body>
</html>
