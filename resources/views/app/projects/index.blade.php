@extends('layouts.app')

@section('title', __('projects.title'))
@section('header', __('projects.title'))

@section('content')
<p class="text-sm text-ziifra-muted">{{ __('projects.subtitle') }}</p>

<div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('projects.search_placeholder') }}"
            class="min-w-[12rem] rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        <select name="status" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            <option value="">{{ __('projects.all_statuses') }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm hover:bg-ziifra-cream">{{ __('projects.filter') }}</button>
    </form>
    @if ($canManage)
        <a href="{{ route('projects.create') }}" class="ziifra-btn-primary">{{ __('projects.new') }}</a>
    @endif
</div>

<div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    @if ($projects->isEmpty())
        <p class="p-8 text-center text-sm text-ziifra-muted">{{ __('projects.empty') }}</p>
    @else
        <div class="divide-y divide-ziifra-line/60">
            @foreach ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 hover:bg-ziifra-cream/40">
                    <div>
                        <p class="font-semibold text-ziifra-ink">{{ $project->name }}</p>
                        <p class="text-xs text-ziifra-muted">{{ $project->status->label() }} · {{ $project->tasks_count }} tasks · {{ $project->completionPercent() }}%</p>
                    </div>
                    <span class="text-sm text-ziifra-muted">{{ $project->members->count() }} members</span>
                </a>
            @endforeach
        </div>
        @if ($projects->hasPages())
            <div class="border-t px-4 py-3">{{ $projects->links() }}</div>
        @endif
    @endif
</div>
@endsection
