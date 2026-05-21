@props([
    'label',
    'value',
    'href' => null,
    'variant' => 'default',
    'hint' => null,
])

@php
    $tag = $href ? 'a' : 'article';
    $variantClass = match ($variant) {
        'warn' => 'ziifra-dashboard-stat-warn',
        'alert' => 'ziifra-dashboard-stat-alert',
        default => '',
    };
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['ziifra-dashboard-stat group', $variantClass]) }}
    @if ($href) aria-label="{{ $label }}: {{ $value }}" @endif
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide {{ $variant === 'warn' ? 'text-amber-800' : 'text-ziifra-muted' }}">
                {{ $label }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight {{ $variant === 'warn' ? 'text-amber-950' : 'text-ziifra-ink' }}">
                {{ $value }}
            </p>
            @if ($hint)
                <p class="mt-1 text-xs {{ $variant === 'warn' ? 'text-amber-800/90' : 'text-ziifra-muted' }}">{{ $hint }}</p>
            @endif
        </div>
        @isset($icon)
            <span class="ziifra-dashboard-stat-icon" aria-hidden="true">{{ $icon }}</span>
        @endisset
    </div>
    @isset($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
