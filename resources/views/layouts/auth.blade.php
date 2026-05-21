<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($authMode = trim($__env->yieldContent('auth_mode')))
@php($authViewport = in_array($authMode, ['login', 'register'], true))
<body @class([
    'ziifra-auth-shell antialiased',
    $authViewport ? 'ziifra-auth-viewport' : '',
])>
    <div @class([
        'ziifra-auth-layout',
        $authViewport ? 'h-dvh max-h-dvh overflow-hidden lg:items-stretch' : 'min-h-screen',
        'lg:grid lg:grid-cols-[1fr_1.05fr] xl:grid-cols-[0.95fr_1fr]',
    ])>
        <aside @class([
            'ziifra-mesh ziifra-auth-aside hidden lg:flex',
            $authViewport ? 'ziifra-auth-aside-viewport' : 'lg:sticky lg:top-0 lg:h-screen lg:self-start',
        ])>
            <div class="ziifra-grid-pattern pointer-events-none absolute inset-0 opacity-50" aria-hidden="true"></div>
            <p class="ziifra-watermark left-6 top-6 !text-[clamp(3rem,8vw,6rem)]" aria-hidden="true">ZIIFRA</p>

            <div class="relative">
                <x-ziifra-logo variant="light" />
                <p @class([
                    'ziifra-label !text-ziifra-accent-glow before:!bg-ziifra-accent-glow',
                    $authViewport ? 'mt-8' : 'mt-12',
                ])>Kosovo · EUR · {{ app(\App\Services\LocaleConfigurationService::class)->enabledOptions()[app()->getLocale()] ?? 'English' }}</p>
                <h2 @class([
                    'ziifra-display max-w-sm font-semibold leading-tight text-white',
                    $authViewport ? 'mt-4 text-2xl' : 'mt-5 text-3xl',
                ])>
                    @yield('aside_heading', __('auth_pages.layout.default_aside_heading'))
                </h2>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-white/55">
                    @yield('aside_text', __('auth_pages.layout.default_aside_text'))
                </p>
            </div>

            <ul @class([
                'relative mt-auto',
                $authViewport ? 'space-y-3 pt-8' : 'space-y-4 pt-12',
            ])>
                @hasSection('aside_points')
                    @yield('aside_points')
                @else
                    <li class="ziifra-auth-trust-item">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-ziifra-paper/10 text-ziifra-accent-glow">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </span>
                        <span>{{ __('auth_pages.layout.trust_secure_workspaces') }}</span>
                    </li>
                    <li class="ziifra-auth-trust-item">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-ziifra-paper/10 text-ziifra-accent-glow">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span>{{ __('auth_pages.layout.trust_trial') }}</span>
                    </li>
                    <li class="ziifra-auth-trust-item">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-ziifra-paper/10 text-ziifra-accent-glow">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <span>{{ __('auth_pages.layout.trust_org_scoped') }}</span>
                    </li>
                @endif
            </ul>

            @hasSection('aside_extra')
                <div class="relative mt-10">
                    @yield('aside_extra')
                </div>
            @endif
        </aside>

        <div @class([
            'ziifra-auth-form-panel',
            $authViewport ? 'h-full min-h-0' : 'min-h-screen',
        ])>
            <header class="flex shrink-0 items-center justify-between border-b border-ziifra-line/60 px-4 py-3 sm:px-8">
                <div class="lg:hidden">
                    <x-ziifra-logo class="!gap-2" variant="auto" />
                </div>
                <div class="hidden flex-1 lg:block" aria-hidden="true"></div>
                <div class="ml-auto flex items-center gap-3">
                    <x-theme-switcher />
                    <x-locale-switcher />
                    <a href="{{ route('home') }}" class="text-sm font-medium text-ziifra-muted transition hover:text-ziifra-accent-deep">
                        {{ __('auth_pages.layout.back_to_home') }}
                    </a>
                </div>
            </header>

            <main @class([
                'flex min-h-0 flex-1 flex-col px-4 sm:px-8 lg:px-14',
                $authViewport ? 'overflow-y-auto overscroll-contain py-5 lg:py-8' : 'justify-center py-10 lg:py-14',
                $authMode === 'login' ? 'justify-center' : '',
            ])>
                <div class="mx-auto w-full max-w-md">
                    @include('partials.flash')
                    @yield('content')
                </div>
            </main>

            <footer class="shrink-0 border-t border-ziifra-line/60 px-4 py-3 text-center text-xs text-ziifra-muted sm:px-8">
                &copy; {{ date('Y') }} ZIIFRA
            </footer>
        </div>
    </div>
</body>
</html>
