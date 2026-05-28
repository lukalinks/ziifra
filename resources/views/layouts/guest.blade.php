<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title') — {{ (View::shared('socialOrganization'))?->name ?? config('app.name') }}</title>
    @include('partials.social-meta')
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ziifra-marketing min-h-screen flex flex-col">
    <header class="border-b border-ziifra-line/60 bg-ziifra-paper/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-md items-center justify-between px-4 py-4 sm:max-w-lg">
            <x-ziifra-logo class="!gap-2" variant="auto" />
            <div class="flex items-center gap-3">
                <x-theme-switcher />
                <x-locale-switcher />
                <a href="{{ route('home') }}" class="text-sm text-ziifra-muted transition hover:text-ziifra-accent-deep">Back to home</a>
            </div>
        </div>
    </header>
    <main class="relative flex flex-1 items-center justify-center px-4 py-12">
        <div class="pointer-events-none absolute inset-0 ziifra-grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative w-full max-w-md">
            @include('partials.flash')
            <div class="ziifra-auth-card">
                @yield('content')
            </div>
        </div>
    </main>
</body>
</html>
