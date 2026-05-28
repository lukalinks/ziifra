<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f6f5f2">
    <title>@yield('title', __('common.default_header')) — {{ \App\Support\CurrentOrganization::get()?->name ?? config('app.name') }}</title>
    @include('partials.social-meta')
    @include('partials.theme-init')
    <script>
        (function () {
            try {
                if (sessionStorage.getItem('ziifra-page-loading') === '1') {
                    document.documentElement.classList.add('ziifra-page-loading');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    use App\Enums\OrganizationRole;

    $billingOrg = \App\Support\CurrentOrganization::get();
    $billingService = app(\App\Services\OrganizationBillingService::class);
    $trialDaysRemaining = $billingOrg && $billingService->isOnTrial($billingOrg)
        ? $billingService->trialDaysRemaining($billingOrg)
        : null;
    $canManageBilling = $billingOrg && auth()->check()
        && (auth()->user()->roleIn($billingOrg)?->canManageBilling() ?? false);
    $showTrialUpgrade = $trialDaysRemaining !== null && $canManageBilling;
    $isEmployeePortal = $billingOrg && auth()->check()
        && auth()->user()->roleIn($billingOrg) === OrganizationRole::Employee;
    $userInitials = collect(explode(' ', trim(auth()->user()->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
<body @class([
    'ziifra-app',
    'ziifra-portal-employee' => $isEmployeePortal,
])>
    <div class="min-h-screen min-h-[100dvh]">
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-ziifra-line/80 bg-ziifra-paper/90 shadow-[1px_0_0_rgba(15,23,42,0.04)] backdrop-blur-xl md:flex">
            <div class="flex h-16 shrink-0 items-center border-b border-ziifra-line/80 px-5">
                <x-ziifra-logo href="{{ $billingOrg ? \App\Support\Workspace::route('dashboard', $billingOrg) : route('workspace.dashboard') }}" variant="auto" data-page-nav />
            </div>
            <x-workspace-nav variant="sidebar" show-icons :show-trial-upgrade="$showTrialUpgrade && request()->routeIs('dashboard')" />
            <div class="shrink-0 border-t border-ziifra-line/80 bg-ziifra-paper/70 p-4 text-xs text-ziifra-muted">
                @if($org = \App\Support\CurrentOrganization::get())
                    <div class="rounded-2xl border border-ziifra-line/70 bg-ziifra-surface/70 p-3">
                    <div class="flex items-center gap-2">
                        @if ($org->hasLogo())
                            <img src="{{ route('settings.company.logo') }}?v={{ $org->updated_at?->timestamp }}"
                                alt="" class="h-8 w-8 shrink-0 rounded-md border border-ziifra-line/80 object-contain bg-ziifra-surface p-0.5">
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-ziifra-ink truncate">{{ $org->name }}</p>
                            @if ($org->brand_tagline)
                                <p class="truncate text-ziifra-muted">{{ $org->brand_tagline }}</p>
                            @endif
                        </div>
                    </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="ziifra-logout-button w-full">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        {{ __('common.log_out') }}
                    </button>
                </form>
            </div>
        </aside>

        <div class="relative min-w-0 md:pl-64">
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-ziifra-line/80 bg-ziifra-paper/80 px-3 backdrop-blur-xl sm:gap-3 sm:px-4 md:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ziifra-ink md:text-base">@yield('header', __('common.default_header'))</p>
                        @if ($isEmployeePortal && $org)
                            <p class="truncate text-xs text-ziifra-muted md:hidden">{{ $org->name }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <x-theme-switcher />
                    <x-locale-switcher />
                    @isset($notificationFeed)
                        <x-notification-bell :feed="$notificationFeed" />
                    @endisset
                    @if (auth()->user()->isSuperAdmin() && ! session('impersonator_id'))
                        <a href="{{ route('admin.dashboard') }}" class="hidden text-sm text-ziifra-accent-deep hover:underline sm:inline">{{ __('common.admin_link') }}</a>
                    @endif
                    <details class="ziifra-user-menu relative hidden sm:block">
                        <summary class="ziifra-user-menu-trigger">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ziifra-accent/15 text-xs font-semibold text-ziifra-accent-deep">{{ $userInitials }}</span>
                            <span class="max-w-[8rem] truncate text-ziifra-muted">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 text-ziifra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </summary>
                        <div class="ziifra-user-menu-panel">
                            <div class="border-b border-ziifra-line/70 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-ziifra-ink">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-ziifra-muted">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="ziifra-user-menu-logout">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                    {{ __('common.log_out') }}
                                </button>
                            </form>
                        </div>
                    </details>
                    <form method="POST" action="{{ route('logout') }}" class="sm:hidden">
                        @csrf
                        <button type="submit" class="ziifra-logout-button !h-9 !px-3 !text-xs">{{ __('common.log_out') }}</button>
                    </form>
                </div>
            </header>

            <main class="ziifra-main min-w-0 flex-1 p-3 sm:p-4 md:p-8">
                @if (session('impersonator_id'))
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                        <span>{{ __('common.impersonating', ['email' => auth()->user()->email]) }}</span>
                        <form method="POST" action="{{ route('impersonation.stop') }}">
                            @csrf
                            <button type="submit" class="font-medium underline hover:no-underline">{{ __('common.stop_impersonating') }}</button>
                        </form>
                    </div>
                @endif
                @if (request()->routeIs('dashboard'))
                    @include('partials.trial-upgrade-banner', ['compact' => true])
                @endif
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>

    @if (\App\Support\CurrentOrganization::get())
        <div id="ziifra-mobile-nav" class="ziifra-mobile-nav md:hidden" aria-hidden="true" inert>
            <div class="ziifra-mobile-nav-backdrop" data-mobile-nav-close tabindex="-1" aria-hidden="true"></div>
            <aside class="ziifra-mobile-nav-panel" role="dialog" aria-modal="true" aria-label="{{ __('navigation.mobile_nav') }}">
                <div class="flex h-16 shrink-0 items-center justify-between border-b border-ziifra-line/80 px-4">
                    <div class="min-w-0">
                        <x-ziifra-logo href="{{ $billingOrg ? \App\Support\Workspace::route('dashboard', $billingOrg) : route('workspace.dashboard') }}" variant="auto" data-page-nav />
                        <p class="mt-0.5 truncate px-0.5 text-xs text-ziifra-muted">{{ __('navigation.browse_all') }}</p>
                    </div>
                    <button type="button" class="ziifra-mobile-menu-btn" data-mobile-nav-close aria-label="{{ __('navigation.close_menu') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <x-workspace-nav variant="sidebar" show-icons :show-trial-upgrade="$showTrialUpgrade" />
                <div class="shrink-0 border-t border-ziifra-line/80 bg-ziifra-paper/70 p-4 text-xs text-ziifra-muted">
                    @if($org = \App\Support\CurrentOrganization::get())
                        <div class="rounded-2xl border border-ziifra-line/70 bg-ziifra-surface/70 p-3">
                            <div class="flex items-center gap-2">
                                @if ($org->hasLogo())
                                    <img src="{{ route('settings.company.logo') }}?v={{ $org->updated_at?->timestamp }}"
                                        alt="" class="h-8 w-8 shrink-0 rounded-md border border-ziifra-line/80 object-contain bg-ziifra-surface p-0.5">
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ziifra-ink">{{ $org->name }}</p>
                                    @if ($org->brand_tagline)
                                        <p class="truncate text-ziifra-muted">{{ $org->brand_tagline }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="ziifra-logout-button w-full">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            {{ __('common.log_out') }}
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <x-workspace-nav variant="mobile" />
    @endif

    <x-page-loader />
    <x-confirm-dialog />
    @stack('scripts')
</body>
</html>
