@props([
    'label',
    'value',
    'href' => null,
    'variant' => 'default',
    'hint' => null,
    'trend' => null,
    'trendUp' => null,
    'iconTone' => 'accent',
])

@php
    $tag = $href ? 'a' : 'article';
    $variantClass = match ($variant) {
        'warn' => 'ziifra-dashboard-stat-warn',
        'alert' => 'ziifra-dashboard-stat-alert',
        default => '',
    };
    $iconToneClass = match ($iconTone) {
        'sky' => 'ziifra-dashboard-stat-icon-sky',
        'amber' => 'ziifra-dashboard-stat-icon-amber',
        'copper' => 'ziifra-dashboard-stat-icon-copper',
        default => '',
    };
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['ziifra-dashboard-stat group', $variantClass]) }}
    @if ($href) aria-label="{{ $label }}: {{ $value }}" @endif
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-ziifra-muted">{{ $label }}</p>
            <p class="mt-2 text-xl font-semibold tabular-nums tracking-tight text-ziifra-ink sm:text-2xl lg:text-3xl">
                {{ $value }}
            </p>
            @if ($trend)
                <p @class([
                    'mt-2 inline-flex items-center gap-1 text-xs font-medium',
                    'text-emerald-600' => $trendUp === true,
                    'text-red-600' => $trendUp === false,
                    'text-ziifra-muted' => $trendUp === null,
                ])>
                    @if ($trendUp === true)
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                    @elseif ($trendUp === false)
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0h11.25m-11.25 0V8.25"/></svg>
                    @endif
                    {{ $trend }}
                </p>
            @elseif ($hint)
                <p class="mt-2 text-xs text-ziifra-muted">{{ $hint }}</p>
            @endif
        </div>
        @isset($icon)
            <span @class(['ziifra-dashboard-stat-icon', $iconToneClass]) aria-hidden="true">{{ $icon }}</span>
        @endisset
    </div>
    @isset($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
