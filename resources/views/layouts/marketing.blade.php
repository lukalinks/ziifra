<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('landing.meta_description') }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title', config('app.name').' — HR for Kosovo')</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ziifra-marketing">
    <header class="ziifra-marketing-header">
        <div class="ziifra-marketing-header__bar mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <x-ziifra-logo variant="auto" class="ziifra-marketing-header__logo" />

            <nav class="ziifra-marketing-nav hidden items-center gap-1 lg:flex" aria-label="Primary">
                <a href="{{ route('home') }}#features" class="ziifra-marketing-nav__link">{{ __('landing.nav.features') }}</a>
                <a href="{{ route('home') }}#pricing" class="ziifra-marketing-nav__link">{{ __('landing.nav.pricing') }}</a>
                <a href="{{ route('home') }}#faq" class="ziifra-marketing-nav__link">{{ __('landing.nav.faq') }}</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                <x-theme-switcher />
                <x-locale-switcher />
                <div class="hidden items-center gap-2 md:flex">
                    @auth
                        <a href="{{ route('workspace.dashboard') }}" class="ziifra-marketing-nav__link">{{ __('landing.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="ziifra-marketing-nav__link">{{ __('landing.nav.login') }}</a>
                        <a href="{{ route('register') }}" class="ziifra-btn-primary !px-5 !py-2 !text-sm">{{ __('landing.nav.trial') }}</a>
                    @endauth
                </div>
                <details class="ziifra-marketing-menu lg:hidden">
                    <summary class="ziifra-marketing-menu__trigger" aria-label="{{ __('landing.nav.menu') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </summary>
                    <div class="ziifra-marketing-menu__panel">
                        <a href="{{ route('home') }}#features" class="ziifra-marketing-menu__link">{{ __('landing.nav.features') }}</a>
                        <a href="{{ route('home') }}#pricing" class="ziifra-marketing-menu__link">{{ __('landing.nav.pricing') }}</a>
                        <a href="{{ route('home') }}#faq" class="ziifra-marketing-menu__link">{{ __('landing.nav.faq') }}</a>
                        @auth
                            <a href="{{ route('workspace.dashboard') }}" class="ziifra-marketing-menu__link">{{ __('landing.nav.dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="ziifra-marketing-menu__link">{{ __('landing.nav.login') }}</a>
                            <a href="{{ route('register') }}" class="ziifra-btn-primary mt-2 w-full text-center !py-2.5 !text-sm">{{ __('landing.nav.trial') }}</a>
                        @endauth
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="ziifra-marketing-footer">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-start">
                <div class="max-w-md">
                    <x-ziifra-logo variant="light" />
                    <p class="mt-6 text-sm leading-relaxed text-white/55">
                        {{ __('landing.footer.blurb') }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-10 sm:grid-cols-3">
                    <div>
                        <p class="ziifra-label !text-ziifra-accent-glow before:!bg-ziifra-accent">{{ __('landing.footer.product') }}</p>
                        <ul class="mt-5 space-y-3 text-sm text-white/60">
                            <li><a href="{{ route('home') }}#features" class="transition hover:text-white">{{ __('landing.nav.features') }}</a></li>
                            <li><a href="{{ route('home') }}#pricing" class="transition hover:text-white">{{ __('landing.nav.pricing') }}</a></li>
                            <li><a href="{{ route('home') }}#faq" class="transition hover:text-white">{{ __('landing.nav.faq') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="ziifra-label !text-ziifra-accent-glow before:!bg-ziifra-accent">{{ __('landing.footer.legal') }}</p>
                        <ul class="mt-5 space-y-3 text-sm text-white/60">
                            <li><a href="{{ route('privacy') }}" class="transition hover:text-white">{{ __('landing.footer.privacy') }}</a></li>
                            <li><a href="{{ route('terms') }}" class="transition hover:text-white">{{ __('landing.footer.terms') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="ziifra-label !text-ziifra-accent-glow before:!bg-ziifra-accent">{{ __('landing.footer.contact') }}</p>
                        <p class="mt-5 text-sm text-white/60">
                            <a href="mailto:support@ziifra.com" class="transition hover:text-ziifra-accent-glow">support@ziifra.com</a>
                        </p>
                    </div>
                </div>
            </div>
            <p class="mt-14 border-t border-white/10 pt-8 text-center text-xs text-white/35">
                &copy; {{ date('Y') }} ZIIFRA. {{ __('landing.footer.rights') }}
            </p>
        </div>
    </footer>
</body>
</html>
