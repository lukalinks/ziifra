@php
    use App\Enums\ProjectStatus;

    $completion = $project->completionPercent();
    $teamCount = $project->members->count();
    $statusTone = match ($project->status) {
        ProjectStatus::Active => 'active',
        ProjectStatus::Planning => 'planning',
        ProjectStatus::OnHold => 'hold',
        ProjectStatus::Completed => 'done',
        ProjectStatus::Cancelled => 'cancelled',
    };
@endphp

<article class="ziifra-project-compact-card">
    <a href="{{ route('projects.show', $project) }}" class="ziifra-project-compact-card-main" data-page-nav>
        <span class="ziifra-project-compact-card-icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($project->name, 0, 1)) }}</span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $project->name }}</span>
            @if ($project->description)
                <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $project->description }}</span>
            @endif
            <span class="mt-2 flex flex-wrap items-center gap-1.5">
                <span @class(['ziifra-project-detail-status !text-[0.6rem]', 'ziifra-project-detail-status-'.$statusTone])>{{ $project->status->label() }}</span>
                <span class="text-[0.65rem] text-ziifra-muted">{{ $project->tasks_count }} {{ __('projects.tasks') }}</span>
            </span>
            <span class="mt-2 flex items-center gap-2">
                <span class="ziifra-project-compact-card-progress" style="--progress: {{ $completion }}%"></span>
                <span class="text-[0.65rem] font-medium tabular-nums text-ziifra-ink">{{ $completion }}%</span>
            </span>
            <span class="mt-1 block text-[0.65rem] text-ziifra-muted">{{ trans_choice('projects.team_count', $teamCount, ['count' => $teamCount]) }}</span>
        </span>
    </a>
    <div class="ziifra-project-compact-card-actions">
        <a href="{{ route('projects.show', $project) }}" class="ziifra-project-compact-card-link" data-page-nav>{{ __('common.view') }}</a>
        @if ($canManage ?? false)
            <a href="{{ route('projects.edit', $project) }}" class="ziifra-project-compact-card-link" data-page-nav>{{ __('projects.edit') }}</a>
        @endif
    </div>
</article>
