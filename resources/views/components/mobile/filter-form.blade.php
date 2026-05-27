@props([
    'action',
    'searchName' => 'search',
    'searchPlaceholder' => null,
    'searchValue' => '',
    'searchId' => 'mobile-list-search',
    'showSearch' => true,
    'clearHref' => null,
    'activeFilterCount' => 0,
    'hasFilters' => false,
    'filtersLabel' => null,
    'filterLabel' => null,
    'clearLabel' => null,
    'showFilterPanel' => true,
])

@php
    $filtersLabel ??= __('common.filters');
    $filterLabel ??= __('common.filter');
    $clearLabel ??= __('common.clear_filters');
    $searchPlaceholder ??= __('common.search');
@endphp

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'ziifra-mobile-list-filters']) }}>
    @isset($beforeSearch)
        {{ $beforeSearch }}
    @endisset

    @if ($showSearch)
        <div class="ziifra-mobile-list-search">
            <svg class="ziifra-mobile-list-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <label for="{{ $searchId }}" class="sr-only">{{ $searchPlaceholder }}</label>
            <input id="{{ $searchId }}" name="{{ $searchName }}" type="search"
                placeholder="{{ $searchPlaceholder }}"
                value="{{ $searchValue }}"
                data-employees-search
                class="ziifra-mobile-list-search-input">
        </div>
    @endif

    @if ($showFilterPanel)
        <details class="ziifra-mobile-list-filter-details" @if ($activeFilterCount > 0) open @endif>
            <summary class="ziifra-mobile-list-filter-summary">
                <span>{{ $filtersLabel }}</span>
                @if ($activeFilterCount > 0)
                    <span class="ziifra-mobile-list-filter-count">{{ $activeFilterCount }}</span>
                @endif
            </summary>

            <div class="ziifra-mobile-list-filter-panel">
                {{ $filters ?? $slot }}

                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="submit" class="ziifra-btn-app flex-1 !py-2.5">{{ $filterLabel }}</button>
                    @if ($hasFilters && $clearHref)
                        <a href="{{ $clearHref }}" data-page-nav class="ziifra-btn-app-outline flex-1 text-center !py-2.5">{{ $clearLabel }}</a>
                    @endif
                </div>
            </div>
        </details>
    @else
        {{ $slot }}
    @endif
</form>
