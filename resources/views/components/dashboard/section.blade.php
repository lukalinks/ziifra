@props([
    'title',
    'description' => null,
    'compact' => false,
])

<section {{ $attributes->merge(['class' => 'ziifra-dashboard-section'.($compact ? ' ziifra-dashboard-section-compact' : '')]) }}>
    <div class="ziifra-dashboard-section-head">
        <h2 class="ziifra-dashboard-section-title">{{ $title }}</h2>
        @if ($description)
            <p class="ziifra-dashboard-section-desc">{{ $description }}</p>
        @endif
    </div>
    <div class="ziifra-dashboard-section-body">
        {{ $slot }}
    </div>
</section>
