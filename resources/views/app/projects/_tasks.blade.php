@php
    use App\Enums\ProjectTaskStatus;
@endphp

<div class="grid gap-4 lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)] lg:items-start">
    <section>
        @if ($canManage)
            <details class="ziifra-project-task-add mb-4">
                <summary>{{ __('projects.add_task') }}</summary>
                <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="space-y-3 border-t border-ziifra-line/60 p-4">
                    @csrf
                    <input type="text" name="title" placeholder="{{ __('projects.task_title') }}" required class="ziifra-input !py-2 !text-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select name="status" class="ziifra-input !py-2 !text-sm">
                            @foreach ($taskStatuses as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="ziifra-input !py-2 !text-sm">
                            @foreach ($taskPriorities as $p)
                                <option value="{{ $p->value }}">{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <select name="assigned_employee_id" class="ziifra-input !py-2 !text-sm">
                        <option value="">{{ __('projects.assignee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                        @endforeach
                    </select>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <input type="date" name="due_date" class="ziifra-input !py-2 !text-sm">
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="checkbox" name="is_milestone" value="1" class="rounded border-ziifra-line text-ziifra-accent-deep">
                            {{ __('projects.milestone') }}
                        </label>
                        <button type="submit" class="ziifra-btn-primary !py-2 !text-sm sm:ml-auto">{{ __('projects.add_task') }}</button>
                    </div>
                </form>
            </details>
        @endif

        @forelse ($project->tasks->where('is_milestone', false) as $task)
            <div class="ziifra-project-task-row">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-ziifra-ink">{{ $task->title }}</p>
                        <span @class([
                            'ziifra-project-task-status',
                            'ziifra-project-task-status-todo' => $task->status === ProjectTaskStatus::Todo,
                            'ziifra-project-task-status-progress' => $task->status === ProjectTaskStatus::InProgress,
                            'ziifra-project-task-status-done' => $task->status === ProjectTaskStatus::Done,
                        ])>{{ $task->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-ziifra-muted">
                        {{ $task->priority->label() }}
                        @if ($task->assignee)
                            · {{ $task->assignee->fullName() }}
                        @endif
                        @if ($task->due_date)
                            · {{ $task->due_date->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                @if ($canManage)
                    <div class="flex shrink-0 items-center gap-2">
                        <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                            @csrf
                            @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="ziifra-input !py-1 !text-xs">
                                @foreach ($taskStatuses as $s)
                                    <option value="{{ $s->value }}" @selected($task->status === $s)>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ziifra-employee-profile-danger-btn !px-2 !py-1 !text-xs" aria-label="{{ __('common.remove') }}">×</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="ziifra-dashboard-empty py-8">
                <p class="text-sm text-ziifra-muted">{{ __('projects.no_tasks') }}</p>
            </div>
        @endforelse
    </section>

    <section class="rounded-xl border border-ziifra-line/70 bg-ziifra-cream/15 p-4">
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('projects.milestones') }}</h2>
        <p class="mt-0.5 text-xs text-ziifra-muted">{{ trans_choice('projects.milestone_count', $milestoneCount ?? $project->tasks->where('is_milestone', true)->count(), ['count' => $project->tasks->where('is_milestone', true)->count()]) }}</p>
        <div class="mt-4 space-y-0">
            @php $milestones = $project->tasks->where('is_milestone', true); @endphp
            @forelse ($milestones as $milestone)
                <div class="ziifra-project-milestone-row">
                    <span class="ziifra-project-milestone-icon" aria-hidden="true">◆</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-ziifra-ink">{{ $milestone->title }}</p>
                        <p class="text-xs text-ziifra-muted">{{ $milestone->status->label() }}</p>
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-ziifra-muted">{{ __('projects.no_milestones') }}</p>
            @endforelse
        </div>
    </section>
</div>
