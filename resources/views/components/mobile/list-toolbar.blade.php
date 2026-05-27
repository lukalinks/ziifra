@props([
    'count' => null,
    'primaryHref' => null,
    'primaryLabel' => null,
])

<div {{ $attributes->merge(['class' => 'ziifra-mobile-list-toolbar md:hidden']) }}>
    @if ($count !== null || ($primaryHref && $primaryLabel))
        <div class="ziifra-mobile-list-head">
            @if ($count !== null)
                <p class="text-sm text-ziifra-muted">{{ $count }}</p>
            @else
                <span aria-hidden="true"></span>
            @endif
            @if ($primaryHref && $primaryLabel)
                <a href="{{ $primaryHref }}" class="ziifra-btn-app shrink-0 !px-3 !py-2 !text-xs">{{ $primaryLabel }}</a>
            @endif
        </div>
    @endif

    {{ $slot }}

    @isset($actions)
        <div class="ziifra-mobile-list-actions">{{ $actions }}</div>
    @endisset
</div>
