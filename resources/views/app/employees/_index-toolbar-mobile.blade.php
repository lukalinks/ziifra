@php
    $hasFilters = request()->filled('search')
        || request()->filled('project_id')
        || request()->filled('type')
        || request()->filled('status')
        || request()->filled('department_id')
        || ($filterMissingLogin ?? false);

    $activeFilterCount = collect([
        request('search'),
        request('project_id'),
        request('type'),
        request('status'),
        request('department_id'),
        ($filterMissingLogin ?? false) ? '1' : null,
    ])->filter(fn ($value) => filled($value))->count();
@endphp

<x-mobile.list-toolbar
    :count="__('employees.count', ['count' => $employees->total()])"
    :primary-href="$canManage ? route('employees.create') : null"
    :primary-label="$canManage ? __('employees.add_employee') : null">
    <x-mobile.filter-form
        :action="route('employees.index')"
        search-id="employees-search-mobile"
        :search-placeholder="__('employees.search_placeholder')"
        :search-value="request('search', '')"
        :clear-href="route('employees.index')"
        :active-filter-count="$activeFilterCount"
        :has-filters="$hasFilters"
        data-employees-quick-filter>
        <x-slot:beforeSearch>
            <div>
                <label for="project_id-mobile" class="ziifra-label-field">{{ __('employees.search_project') }}</label>
                <select id="project_id-mobile" name="project_id" data-employees-project class="ziifra-input">
                    <option value="">{{ __('employees.all_projects') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:beforeSearch>
        <x-slot:filters>
            <div>
                <label for="type-mobile" class="ziifra-label-field">{{ __('employees.field_type') }}</label>
                <select id="type-mobile" name="type" class="ziifra-input">
                    <option value="">{{ __('employees.all_types') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status-mobile" class="ziifra-label-field">{{ __('employees.field_status') }}</label>
                <select id="status-mobile" name="status" class="ziifra-input">
                    <option value="">{{ __('employees.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="department_id-mobile" class="ziifra-label-field">{{ __('employees.field_department') }}</label>
                <select id="department_id-mobile" name="department_id" class="ziifra-input">
                    <option value="">{{ __('employees.all_departments') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($canActivateLogin ?? false)
                <label class="flex items-center gap-2 rounded-xl border border-ziifra-line/70 bg-ziifra-paper px-3 py-2.5 text-sm text-ziifra-ink">
                    <input type="checkbox" name="missing_login" value="1" @checked($filterMissingLogin ?? false)
                        class="rounded border-ziifra-line text-ziifra-accent-deep focus:ring-ziifra-accent">
                    {{ __('employees.filter_missing_login') }}
                </label>
            @endif
        </x-slot:filters>
    </x-mobile.filter-form>

    @if ($selectedProject)
        <p class="text-xs text-ziifra-muted">{{ __('employees.filter_project_active', ['project' => $selectedProject->name]) }}</p>
    @endif

    @if ($hasFilters && request('search'))
        <div class="ziifra-mobile-list-active-filters">
            <span class="ziifra-mobile-list-filter-chip">{{ request('search') }}</span>
        </div>
    @endif

    @if ($filterMissingLogin ?? false)
        <p class="text-xs text-ziifra-muted">{{ __('employees.filter_missing_login_active') }}</p>
    @endif

    @if ($canManage)
        <x-slot:actions>
            <a href="{{ route('employees.export') }}" class="ziifra-btn-app-outline">{{ __('employees.export_csv') }}</a>
            <a href="{{ route('employees.import') }}" class="ziifra-btn-app-outline">{{ __('employees.import_csv') }}</a>
        </x-slot:actions>
    @endif
</x-mobile.list-toolbar>
