@extends('layouts.app')

@section('title', __('projects.title'))
@section('header', __('projects.title'))

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('status');
    $activeFilterCount = collect([request('search'), request('status')])->filter(fn ($v) => filled($v))->count();
@endphp

<div class="ziifra-dashboard-page ziifra-projects-index">
    <x-mobile.list-toolbar
        :count="__('projects.count', ['count' => $projects->total()])"
        :primary-href="$canManage ? route('projects.create') : null"
        :primary-label="$canManage ? __('projects.new') : null">
        <x-mobile.filter-form
            :action="route('projects.index')"
            search-id="projects-search-mobile"
            :search-placeholder="__('projects.search_placeholder')"
            :search-value="request('search', '')"
            :clear-href="route('projects.index')"
            :active-filter-count="$activeFilterCount"
            :has-filters="$hasFilters">
            <x-slot:filters>
                <div>
                    <label for="status-mobile" class="ziifra-label-field">{{ __('projects.status') }}</label>
                    <select id="status-mobile" name="status" class="ziifra-input">
                        <option value="">{{ __('projects.all_statuses') }}</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </x-slot:filters>
        </x-mobile.filter-form>

        @if ($hasFilters && request('status'))
            <div class="ziifra-mobile-list-active-filters">
                <span class="ziifra-mobile-list-filter-chip">{{ collect($statuses)->first(fn ($s) => $s->value === request('status'))?->label() }}</span>
            </div>
        @endif
    </x-mobile.list-toolbar>

    <section class="ziifra-index-toolbar">
        <div class="ziifra-index-toolbar-head">
            <div class="min-w-0">
                <p class="text-sm text-ziifra-muted">{{ __('projects.subtitle') }}</p>
                <p class="mt-1 text-sm font-medium text-ziifra-ink">{{ __('projects.count', ['count' => $projects->total()]) }}</p>
            </div>
            @if ($canManage)
                <div class="ziifra-index-toolbar-actions">
                    <a href="{{ route('projects.create') }}" class="ziifra-btn-app !py-2 !text-sm" data-page-nav>{{ __('projects.new') }}</a>
                </div>
            @endif
        </div>

        <div class="ziifra-index-toolbar-body">
            <form method="GET" action="{{ route('projects.index') }}" class="ziifra-projects-filter-form">
                <div class="ziifra-projects-filter-search">
                    <label for="search" class="ziifra-label-field">{{ __('projects.search_placeholder') }}</label>
                    <input id="search" name="search" type="search" value="{{ request('search') }}"
                        placeholder="{{ __('projects.search_placeholder') }}"
                        class="ziifra-input !w-full !py-2 !text-sm">
                </div>
                <div class="ziifra-projects-filter-status">
                    <label for="status" class="ziifra-label-field">{{ __('projects.status') }}</label>
                    <select id="status" name="status" class="ziifra-input !w-full !py-2 !text-sm">
                        <option value="">{{ __('projects.all_statuses') }}</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ziifra-projects-filter-submit">
                    <button type="submit" class="ziifra-btn-app-outline w-full !py-2 !text-sm">{{ __('projects.filter') }}</button>
                </div>
            </form>

            @if ($hasFilters)
                <div class="flex flex-wrap gap-2">
                    @if (request('search'))
                        <p class="ziifra-index-active-banner">{{ request('search') }}</p>
                    @endif
                    @if (request('status'))
                        <p class="ziifra-index-active-banner">{{ collect($statuses)->first(fn ($s) => $s->value === request('status'))?->label() }}</p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="ziifra-index-panel">
        <div class="ziifra-index-panel-head md:hidden">
            <p class="text-sm font-medium text-ziifra-ink">{{ __('projects.count', ['count' => $projects->total()]) }}</p>
        </div>

        @if ($projects->isEmpty())
            <div class="ziifra-dashboard-empty py-12">
                <span class="ziifra-dashboard-empty-icon text-sky-500/70">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.25-3.75l3 3m0 0l3-3m-3 3V2.25"/></svg>
                </span>
                <p class="mt-3 font-medium text-ziifra-ink">{{ __('projects.empty') }}</p>
                @if ($canManage)
                    <a href="{{ route('projects.create') }}" class="ziifra-btn-primary mt-4 !text-sm" data-page-nav>{{ __('projects.new') }}</a>
                @endif
            </div>
        @else
            <div class="ziifra-projects-compact-grid p-3 sm:p-4 md:p-5">
                @foreach ($projects as $project)
                    @include('app.projects._index-compact-card', [
                        'project' => $project,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
            @if ($projects->hasPages())
                <div class="border-t border-ziifra-line/80 px-4 py-3 sm:px-5">
                    {{ $projects->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
