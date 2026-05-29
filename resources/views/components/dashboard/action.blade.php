@props(['href', 'icon', 'label'])

<a href="{{ $href }}" {{ $attributes->class(['ziifra-dashboard-action group']) }}>
    <span class="ziifra-dashboard-action-icon" aria-hidden="true">
        <x-dashboard.icons :name="$icon" />
    </span>
    <span class="ziifra-dashboard-action-label">{{ $label }}</span>
    <svg class="ziifra-dashboard-action-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
</a>
