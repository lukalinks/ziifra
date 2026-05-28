@extends('layouts.app')

@section('title', __('invoices.new'))
@section('header', __('invoices.new'))

@section('content')
@if (empty($organization->bank_iban))
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <span>{{ __('invoices.iban_missing_warning') }}</span>
        <a href="{{ route('settings.invoices.edit') }}" class="font-semibold underline">{{ __('invoices.go_to_settings') }}</a>
    </div>
@endif

<div class="mb-4 flex items-center justify-end">
    <a href="{{ route('settings.invoices.edit') }}" class="inline-flex items-center gap-1.5 text-sm text-ziifra-accent-deep hover:underline">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        {{ __('invoices.settings') }}
    </a>
</div>

<div class="grid max-w-5xl gap-6 lg:grid-cols-2">
    <form method="POST" action="{{ route('invoices.from-hours') }}" class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        @csrf
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('invoices.from_hours_heading') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('invoices.from_hours_hint') }}</p>
        <div class="mt-4 space-y-3">
            <x-form.searchable-select
                name="project_id"
                :label="__('invoices.project')"
                :options="$projects->map(fn ($project) => ['value' => (string) $project->id, 'label' => $project->name])->values()->all()"
                :selected="old('project_id', $prefillProjectId)"
                :placeholder="__('invoices.search_project')"
                :empty-text="__('invoices.no_project_matches')"
                required
            />
            <div>
                <label class="block text-sm font-medium">{{ __('invoices.period_start') }}</label>
                <input type="date" name="period_start" required value="{{ old('period_start', $prefillPeriodStart) }}" class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium">{{ __('invoices.period_end') }}</label>
                <input type="date" name="period_end" required value="{{ old('period_end', $prefillPeriodEnd) }}" class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium">{{ __('invoices.client') }}</label>
                <input type="text" name="client_name" class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            </div>
            <button type="submit" class="ziifra-btn-primary !text-sm">{{ __('invoices.generate_from_hours') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('invoices.store') }}" class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        @csrf
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('invoices.manual_heading') }}</h2>
        @include('app.invoices._form')
        <div class="mt-6"><button type="submit" class="ziifra-btn-app-outline !text-sm">{{ __('invoices.save') }}</button></div>
    </form>
</div>
@endsection
