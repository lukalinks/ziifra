@props(['href', 'icon', 'label'])

<a href="{{ $href }}" {{ $attributes->class(['ziifra-dashboard-action group']) }}>
    <span class="ziifra-dashboard-action-icon" aria-hidden="true">
        <x-dashboard.icons :name="$icon" />
    </span>
    <span class="flex-1">{{ $label }}</span>
    <svg class="h-4 w-4 shrink-0 text-ziifra-muted opacity-0 transition group-hover:opacity-100 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
</a>
