@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->class(['ziifra-dashboard-empty flex flex-col items-center justify-center py-10 text-center']) }}>
    @isset($icon)
        <div class="mb-3 text-ziifra-muted" aria-hidden="true">{{ $icon }}</div>
    @endisset
    <p class="text-sm font-medium text-ziifra-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ziifra-muted">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-4 text-sm font-medium text-ziifra-accent-deep hover:underline">
            {{ $actionLabel }} →
        </a>
    @endif
    {{ $slot }}
</div>
