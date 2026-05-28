@props([
    'variant' => 'sidebar',
    'showTrialUpgrade' => false,
    'showIcons' => false,
])

@php
    use App\Enums\OrganizationRole;

    $org = \App\Support\CurrentOrganization::get();
    $user = auth()->user();
    $nav = app(\App\Support\WorkspaceNavigation::class);
    $groups = $nav->groups($org, $user);
    $flat = $nav->flat($org, $user);
    $primaryMobile = $nav->primaryMobile($org, $user);
    $isEmployeePortal = $org && $user && $user->roleIn($org) === OrganizationRole::Employee;

    $primaryRouteNames = collect($primaryMobile)
        ->pluck('route')
        ->filter()
        ->values()
        ->all();

    $activeItem = collect($flat)->first(fn (array $item) => $item['active']);
    $menuHighlightsMore = $activeItem !== null
        && ! in_array($activeItem['route'] ?? null, $primaryRouteNames, true);

    $mobileLabel = static function (string $route, string $label): string {
        return match ($route) {
            'dashboard' => __('navigation.dashboard'),
            'employees.show' => __('navigation.my_profile'),
            'leave.index' => __('navigation.leave'),
            'leave.calendar' => __('navigation.leave'),
            'expenses.index' => __('navigation.expenses'),
            'time.index' => __('navigation.time_and_attendance'),
            'chat.index' => __('navigation.chat'),
            default => $label,
        };
    };
@endphp

@if ($variant === 'sidebar')
    <nav @class([
        'flex-1 overflow-y-auto overscroll-contain text-sm font-medium',
        'px-3 py-4' => ! $showIcons,
        'px-2 py-3' => $showIcons,
    ]) aria-label="Workspace">
        <div class="space-y-5">
            @foreach ($groups as $group)
                <div>
                    <p @class([
                        'mb-2 px-3 font-mono text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-ziifra-muted',
                        'px-2' => $showIcons,
                    ])>
                        {{ $group['label'] }}
                    </p>
                    <div class="space-y-1">
                        @foreach ($group['items'] as $item)
                            @if ($item['enabled'])
                                <a href="{{ $item['href'] ?? route($item['route']) }}"
                                    @if (empty($item['href'])) data-page-nav @endif
                                    @class([
                                        'ziifra-nav-link',
                                        'ziifra-nav-link-with-icon' => $showIcons,
                                        'ziifra-nav-link-active' => $item['active'],
                                    ])>
                                    @if ($showIcons)
                                        <span class="ziifra-nav-link-icon" aria-hidden="true">
                                            <x-nav-icon :route="$item['route'] ?? 'custom'" />
                                        </span>
                                    @endif
                                    <span @class(['min-w-0 flex-1 truncate' => $showIcons])>{{ $item['label'] }}</span>
                                </a>
                            @else
                                <span @class([
                                    'ziifra-nav-link ziifra-nav-link-soon',
                                    'ziifra-nav-link-with-icon' => $showIcons,
                                ])
                                    title="{{ __('navigation.coming_soon_hint') }}">
                                    @if ($showIcons)
                                        <span class="ziifra-nav-link-icon opacity-60" aria-hidden="true">
                                            <x-nav-icon :route="$item['route'] ?? 'custom'" />
                                        </span>
                                    @endif
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                    <span class="ziifra-nav-soon-badge">{{ __('navigation.coming_soon') }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($showTrialUpgrade)
                <div class="pt-2">
                    <a href="{{ route('settings.billing') }}#plans"
                        data-page-nav
                        @class([
                            'ziifra-nav-link !border !border-ziifra-accent/30 !bg-ziifra-accent/10 !text-ziifra-accent-deep',
                            'ziifra-nav-link-with-icon' => $showIcons,
                            'ziifra-nav-link-active' => request()->routeIs('settings.billing'),
                        ])>
                        @if ($showIcons)
                            <span class="ziifra-nav-link-icon" aria-hidden="true">
                                <x-nav-icon route="settings.index" />
                            </span>
                        @endif
                        <span @class(['min-w-0 flex-1 truncate' => $showIcons])>{{ __('billing.upgrade') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </nav>
@else
    <nav class="ziifra-mobile-tabbar md:hidden" aria-label="{{ __('navigation.mobile_nav') }}">
        @foreach ($primaryMobile as $item)
            <a href="{{ $item['href'] ?? route($item['route']) }}"
                @if (empty($item['href'])) data-page-nav @endif
                @class([
                    'ziifra-mobile-tab',
                    'ziifra-mobile-tab-active' => $item['active'],
                ])>
                <x-nav-icon :route="$item['route']" />
                <span class="ziifra-mobile-tab-label">{{ $mobileLabel($item['route'], $item['label']) }}</span>
            </a>
        @endforeach
        <button type="button"
            @class([
                'ziifra-mobile-tab',
                'ziifra-mobile-tab-active' => $menuHighlightsMore,
            ])
            data-mobile-nav-open
            aria-label="{{ __('navigation.open_menu') }}"
            @if ($menuHighlightsMore) aria-current="page" @endif>
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span class="ziifra-mobile-tab-label">{{ __('navigation.menu') }}</span>
        </button>
    </nav>
@endif
