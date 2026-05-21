@extends('layouts.app')

@section('title', $project->name)
@section('header', $project->name)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="text-sm text-ziifra-muted">{{ $project->status->label() }} · {{ $project->completionPercent() }}% {{ __('projects.progress') }}</p>
        @if ($project->description)
            <p class="mt-2 max-w-2xl text-sm text-ziifra-ink">{{ $project->description }}</p>
        @endif
        @if ($project->members->isNotEmpty())
            <p class="mt-2 text-xs text-ziifra-muted">
                {{ __('projects.team') }}:
                {{ $project->members->map(fn ($e) => $e->fullName())->join(', ') }}
            </p>
        @endif
    </div>
    @if ($canManage)
        <div class="flex gap-2">
            <a href="{{ route('projects.edit', $project) }}" class="ziifra-btn-app-outline">{{ __('projects.edit') }}</a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" data-confirm="{{ __('projects.confirm_delete') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('common.delete') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm text-red-700 hover:bg-red-50">{{ __('projects.delete') }}</button>
            </form>
        </div>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-5">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('projects.tasks') }}</h2>
        @if ($canManage)
            <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="mb-4 space-y-3 rounded-lg border border-ziifra-line/60 bg-ziifra-cream/30 p-4">
                @csrf
                <input type="text" name="title" placeholder="{{ __('projects.task_title') }}" required class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <div class="grid gap-3 sm:grid-cols-2">
                    <select name="status" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        @foreach ($taskStatuses as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    <select name="priority" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        @foreach ($taskPriorities as $p)
                            <option value="{{ $p->value }}">{{ $p->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <select name="assigned_employee_id" class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    <option value="">{{ __('projects.assignee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="date" name="due_date" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_milestone" value="1">
                        {{ __('projects.milestone') }}
                    </label>
                    <button type="submit" class="ziifra-btn-primary text-sm">{{ __('projects.add_task') }}</button>
                </div>
            </form>
        @endif
        @forelse ($project->tasks as $task)
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-ziifra-line/40 py-3 first:border-t-0 first:pt-0">
                <div>
                    <p class="font-medium text-ziifra-ink">
                        @if ($task->is_milestone)
                            <span class="mr-1 text-ziifra-accent-deep">◆</span>
                        @endif
                        {{ $task->title }}
                    </p>
                    <p class="text-xs text-ziifra-muted">
                        {{ $task->status->label() }} · {{ $task->priority->label() }}
                        @if ($task->assignee)
                            · {{ $task->assignee->fullName() }}
                        @endif
                        @if ($task->due_date)
                            · {{ $task->due_date->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                @if ($canManage)
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                            @csrf
                            @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="rounded border border-ziifra-line px-2 py-1 text-xs">
                                @foreach ($taskStatuses as $s)
                                    <option value="{{ $s->value }}" @selected($task->status === $s)>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">×</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-ziifra-muted">{{ __('projects.no_tasks') }}</p>
        @endforelse
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-5">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('projects.milestones') }}</h2>
        @php $milestones = $project->tasks->where('is_milestone', true); @endphp
        @forelse ($milestones as $milestone)
            <p class="border-t border-ziifra-line/40 py-2 text-sm first:border-t-0">
                {{ $milestone->title }}
                <span class="text-ziifra-muted">— {{ $milestone->status->label() }}</span>
            </p>
        @empty
            <p class="text-sm text-ziifra-muted">{{ __('projects.no_tasks') }}</p>
        @endforelse
    </section>
</div>
@endsection
