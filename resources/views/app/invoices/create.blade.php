@extends('layouts.app')

@section('title', __('invoices.new'))
@section('header', __('invoices.new'))

@section('content')
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
