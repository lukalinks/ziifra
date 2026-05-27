@php
    $filterQuery = array_filter([
        'project' => $selectedProject?->id,
        'view' => $viewAll && ! $selectedProject ? 'all' : null,
        'search' => request('search'),
        'category' => request('category'),
    ]);
@endphp

<div class="ziifra-documents-panel">
    <div class="ziifra-documents-panel-head ziifra-documents-panel-head--compact">
        <form method="GET" action="{{ route('project-documents.index') }}" class="ziifra-documents-filters">
            @if ($selectedProject)
                <input type="hidden" name="project" value="{{ $selectedProject->id }}">
            @elseif ($viewAll)
                <input type="hidden" name="view" value="all">
            @endif

            <div class="ziifra-documents-filter-search">
                <label for="project-doc-search" class="sr-only">{{ __('documents.search') }}</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ziifra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="search" id="project-doc-search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('project_documents.search_placeholder') }}"
                    class="w-full rounded-lg border border-ziifra-line bg-white py-2 pl-9 pr-3 text-sm">
            </div>

            <div>
                <label for="project-doc-category" class="sr-only">{{ __('project_documents.category') }}</label>
                <select id="project-doc-category" name="category" class="w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm sm:min-w-[10rem]">
                    <option value="">{{ __('project_documents.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected(request('category') === $category->value)>
                            {{ $category->label() }}
                            @if (($categoryCounts[$category->value] ?? 0) > 0)
                                ({{ $categoryCounts[$category->value] }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="ziifra-btn-app-outline !text-sm">{{ __('documents.filter') }}</button>
                @if ($hasFilters)
                    <a href="{{ route('project-documents.index', array_filter(['project' => $selectedProject?->id, 'view' => $viewAll && ! $selectedProject ? 'all' : null])) }}"
                        data-page-nav
                        class="text-sm font-medium text-ziifra-muted hover:text-ziifra-ink">
                        {{ __('documents.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
