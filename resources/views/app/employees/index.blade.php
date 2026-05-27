@extends('layouts.app')

@section('title', __('employees.title'))
@section('header', __('employees.title'))

@section('content')
<div class="ziifra-dashboard-page ziifra-employees-index">
    @include('app.employees._index-toolbar-mobile')

    <section class="ziifra-employees-index-toolbar">
        <div class="ziifra-employees-index-toolbar-head">
            <div class="min-w-0">
                <p class="text-sm text-ziifra-muted">{{ __('employees.subtitle') }}</p>
                <p class="mt-1 text-sm font-medium text-ziifra-ink">{{ __('employees.count', ['count' => $employees->total()]) }}</p>
            </div>
            @if ($canManage)
                <div class="ziifra-employees-index-toolbar-actions">
                    <a href="{{ route('employees.export') }}" class="ziifra-btn-app-outline !py-2 !text-sm">{{ __('employees.export_csv') }}</a>
                    <a href="{{ route('employees.import') }}" class="ziifra-btn-app-outline !py-2 !text-sm">{{ __('employees.import_csv') }}</a>
                    <a href="{{ route('employees.create') }}" class="ziifra-btn-app !py-2 !text-sm">{{ __('employees.add_employee') }}</a>
                </div>
            @endif
        </div>

        <div class="ziifra-employees-index-toolbar-body">
            <form method="GET" action="{{ route('employees.index') }}" class="ziifra-employees-filter-form" data-employees-quick-filter>
                <div class="ziifra-employees-filter-project">
                    <label for="project_id" class="ziifra-label-field">{{ __('employees.search_project') }}</label>
                    <select id="project_id" name="project_id" data-employees-project class="ziifra-input !w-full !py-2 !text-sm">
                        <option value="">{{ __('employees.all_projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ziifra-employees-filter-search">
                    <label for="search" class="ziifra-label-field">{{ __('employees.search_placeholder') }}</label>
                    <input id="search" name="search" type="search" placeholder="{{ __('employees.search_placeholder') }}"
                        value="{{ request('search') }}" data-employees-search
                        class="ziifra-input !w-full !py-2 !text-sm">
                </div>
                <div class="ziifra-employees-filter-type">
                    <label for="type" class="ziifra-label-field">{{ __('employees.field_type') }}</label>
                    <select id="type" name="type" class="ziifra-input !w-full !py-2 !text-sm">
                        <option value="">{{ __('employees.all_types') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ziifra-employees-filter-status">
                    <label for="status" class="ziifra-label-field">{{ __('employees.field_status') }}</label>
                    <select id="status" name="status" class="ziifra-input !w-full !py-2 !text-sm">
                        <option value="">{{ __('employees.all_statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ziifra-employees-filter-department">
                    <label for="department_id" class="ziifra-label-field">{{ __('employees.field_department') }}</label>
                    <select id="department_id" name="department_id" class="ziifra-input !w-full !py-2 !text-sm">
                        <option value="">{{ __('employees.all_departments') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($canActivateLogin ?? false)
                    <label class="ziifra-employees-filter-login">
                        <input type="checkbox" name="missing_login" value="1" @checked($filterMissingLogin ?? false)
                            class="rounded border-ziifra-line text-ziifra-accent-deep focus:ring-ziifra-accent">
                        {{ __('employees.filter_missing_login') }}
                    </label>
                @endif
                <div class="ziifra-employees-filter-submit">
                    <button type="submit" class="ziifra-btn-app-outline w-full !py-2 !text-sm">{{ __('employees.filter') }}</button>
                </div>
            </form>

            @if ($selectedProject || ($filterMissingLogin ?? false))
                <div class="flex flex-wrap gap-2">
                    @if ($selectedProject)
                        <p class="ziifra-employees-active-banner">{{ __('employees.filter_project_active', ['project' => $selectedProject->name]) }}</p>
                    @endif
                    @if ($filterMissingLogin ?? false)
                        <p class="ziifra-employees-active-banner">{{ __('employees.filter_missing_login_active') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="ziifra-employees-index-panel">
        <div class="ziifra-employees-index-panel-head md:hidden">
            <p class="text-sm font-medium text-ziifra-ink">{{ __('employees.count', ['count' => $employees->total()]) }}</p>
        </div>

        @if ($employees->isEmpty())
            <div class="ziifra-dashboard-empty py-12">
                <span class="ziifra-dashboard-empty-icon text-sky-500/70">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </span>
                <p class="mt-3 font-medium text-ziifra-ink">{{ __('employees.empty') }}</p>
                @if ($canManage)
                    <a href="{{ route('employees.create') }}" class="ziifra-btn-primary mt-4 !text-sm" data-page-nav>{{ __('employees.empty_action') }}</a>
                @endif
            </div>
        @else
            <div class="ziifra-employees-compact-grid p-3 sm:p-4 md:p-5">
                @foreach ($employees as $employee)
                    @include('app.employees._index-compact-card', [
                        'employee' => $employee,
                        'pendingLoginInvites' => $pendingLoginInvites,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
            @if ($employees->hasPages())
                <div class="border-t border-ziifra-line/80 px-4 py-3 sm:px-5">
                    {{ $employees->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
