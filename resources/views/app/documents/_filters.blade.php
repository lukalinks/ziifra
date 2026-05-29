<div class="ziifra-documents-panel">
    <div class="ziifra-documents-panel-head ziifra-documents-panel-head--compact">
        <form method="GET" action="{{ route('documents.index') }}" class="ziifra-documents-filters">
            @if ($selectedFolder)
                <input type="hidden" name="folder" value="{{ $selectedFolder->id }}">
            @elseif ($selectedType)
                <input type="hidden" name="type" value="{{ $selectedType }}">
            @endif

            <div class="ziifra-documents-filter-search">
                <label for="search" class="sr-only">{{ __('documents.search') }}</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ziifra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="search" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('documents.search_placeholder') }}"
                    class="ziifra-documents-field !mt-0 py-2 pl-9 pr-3">
            </div>

            <div>
                <label for="employee_id" class="sr-only">{{ __('documents.employee') }}</label>
                <select id="employee_id" name="employee_id" class="ziifra-documents-field px-3 sm:min-w-[10rem]">
                    <option value="">{{ __('documents.all_employees') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                            {{ $employee->fullName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="expiry" class="sr-only">{{ __('documents.expiry_filter') }}</label>
                <select id="expiry" name="expiry" class="ziifra-documents-field px-3 sm:min-w-[9rem]">
                    <option value="">{{ __('documents.all_expiry') }}</option>
                    <option value="expiring" @selected(request('expiry') === 'expiring')>{{ __('documents.filter_expiring') }}</option>
                    <option value="expired" @selected(request('expiry') === 'expired')>{{ __('documents.filter_expired') }}</option>
                    <option value="none" @selected(request('expiry') === 'none')>{{ __('documents.filter_no_expiry') }}</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="ziifra-btn-app-outline !text-sm">
                    {{ __('documents.filter') }}
                </button>
                @if (request()->hasAny(['search', 'employee_id', 'expiry']))
                    <a href="{{ route('documents.index', array_filter(['folder' => $selectedFolder?->id, 'type' => $selectedType])) }}"
                        data-page-nav
                        class="text-sm font-medium text-ziifra-muted hover:text-ziifra-ink">
                        {{ __('documents.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
