@props([
    'href',
    'nav' => true,
    'avatar' => null,
    'showChevron' => true,
])

<a href="{{ $href }}"
    @if ($nav) data-page-nav @endif
    {{ $attributes->merge(['class' => 'ziifra-list-card']) }}>
    @if ($avatar)
        <span class="ziifra-list-card-avatar" aria-hidden="true">{{ $avatar }}</span>
    @endif
    <span class="min-w-0 flex-1">
        {{ $slot }}
    </span>
    @if ($showChevron)
        <svg class="ziifra-list-card-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
    @endif
</a>
