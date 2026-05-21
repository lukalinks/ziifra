@props([
    'variant' => 'sidebar',
    'showTrialUpgrade' => false,
])

@php
    use App\Enums\OrganizationRole;

    $org = \App\Support\CurrentOrganization::get();
    $user = auth()->user();
    $nav = app(\App\Support\WorkspaceNavigation::class);
    $groups = $nav->groups($org, $user);
    $flat = $nav->flat($org, $user);
    $isEmployeePortal = $org && $user && $user->roleIn($org) === OrganizationRole::Employee;

    $mobileLabel = static function (string $route, string $label): string {
        return match ($route) {
            'dashboard' => __('navigation.dashboard'),
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
    <nav class="flex-1 overflow-y-auto overscroll-contain px-3 py-4 text-sm font-medium" aria-label="Workspace">
        <div class="space-y-5">
            @foreach ($groups as $group)
                <div>
                    <p class="mb-2 px-3 font-mono text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-ziifra-muted/75">
                        {{ $group['label'] }}
                    </p>
                    <div class="space-y-1">
                        @foreach ($group['items'] as $item)
                            @if ($item['enabled'])
                                <a href="{{ route($item['route']) }}"
                                    data-page-nav
                                    @class([
                                        'ziifra-nav-link',
                                        'ziifra-nav-link-active' => $item['active'],
                                    ])>
                                    {{ $item['label'] }}
                                </a>
                            @else
                                <span class="ziifra-nav-link ziifra-nav-link-soon"
                                    title="{{ __('navigation.coming_soon_hint') }}">
                                    <span>{{ $item['label'] }}</span>
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
                            'ziifra-nav-link-active' => request()->routeIs('settings.billing'),
                        ])>
                        {{ __('billing.upgrade') }}
                    </a>
                </div>
            @endif
        </div>
    </nav>
@else
    <nav class="ziifra-mobile-tabbar md:hidden" aria-label="{{ __('navigation.mobile_nav') }}">
        @foreach ($flat as $item)
            <a href="{{ route($item['route']) }}"
                data-page-nav
                @class([
                    'ziifra-mobile-tab',
                    'ziifra-mobile-tab-active' => $item['active'],
                ])>
                <x-nav-icon :route="$item['route']" />
                <span class="ziifra-mobile-tab-label">{{ $mobileLabel($item['route'], $item['label']) }}</span>
            </a>
        @endforeach
    </nav>
@endif
