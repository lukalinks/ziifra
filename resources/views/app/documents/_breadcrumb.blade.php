@if ($selectedTypeEnum || $selectedFolder || $hasFilters)
    <nav class="ziifra-documents-breadcrumb" aria-label="{{ __('documents.breadcrumb_documents') }}">
        <a href="{{ route('documents.index') }}" data-page-nav class="ziifra-documents-breadcrumb-link">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            {{ __('documents.breadcrumb_documents') }}
        </a>
        @if ($selectedTypeEnum || $selectedFolder)
            <span class="text-ziifra-muted/60" aria-hidden="true">/</span>
            <span class="font-medium text-ziifra-ink">
                {{ $selectedFolder?->name ?? $selectedTypeEnum?->label() }}
            </span>
        @elseif ($hasFilters)
            <span class="text-ziifra-muted/60" aria-hidden="true">/</span>
            <span class="font-medium text-ziifra-ink">{{ __('documents.filtered_view') }}</span>
        @endif
    </nav>
@endif
