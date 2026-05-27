@extends('layouts.app')

@section('title', $project->name)
@section('header', $project->name)

@section('content')
@php
    use App\Enums\ProjectStatus;

    $completion = $project->completionPercent();
    $teamCount = $project->members->count();
    $taskCount = $project->tasks->count();
    $milestoneCount = $project->tasks->where('is_milestone', true)->count();
    $monthHours = $hoursGrid['totals']['hours'] ?? 0;
    $pendingHours = $hoursGrid['totals']['pending'] ?? 0;
    $monthCarbon = \Carbon\Carbon::parse($selectedMonth.'-01');
    $prevMonth = $monthCarbon->copy()->subMonth()->format('Y-m');
    $nextMonth = $monthCarbon->copy()->addMonth()->format('Y-m');
    $isCurrentMonth = $selectedMonth === now()->format('Y-m');

    $statusTone = match ($project->status) {
        ProjectStatus::Active => 'active',
        ProjectStatus::Planning => 'planning',
        ProjectStatus::OnHold => 'hold',
        ProjectStatus::Completed => 'done',
        ProjectStatus::Cancelled => 'cancelled',
    };
@endphp

<div class="ziifra-dashboard-page ziifra-project-detail">
    @if ($tab !== 'hours')
        <a href="{{ route('projects.index') }}" class="ziifra-employee-profile-back" data-page-nav>
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('projects.back_to_list') }}
        </a>

        <section class="ziifra-project-detail-hero">
        <div class="relative z-[1] grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
            <div class="min-w-0">
                <div class="ziifra-project-detail-hero-main">
                    <span class="ziifra-project-detail-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.25-3.75l3 3m0 0l3-3m-3 3V2.25"/>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span @class(['ziifra-project-detail-status', 'ziifra-project-detail-status-'.$statusTone])>{{ $project->status->label() }}</span>
                            @if ($project->budget)
                                <span class="ziifra-employee-profile-chip">{{ number_format((float) $project->budget, 0) }} {{ $project->currency ?? 'EUR' }}</span>
                            @endif
                        </div>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ziifra-ink sm:text-3xl">{{ $project->name }}</h1>
                        @if ($project->description)
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ziifra-muted">{{ $project->description }}</p>
                        @endif
                        @if ($project->start_date || $project->end_date)
                            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ziifra-muted">
                                @if ($project->start_date)
                                    <span>{{ __('projects.start_date') }}: <strong class="font-medium text-ziifra-ink">{{ $project->start_date->format('M j, Y') }}</strong></span>
                                @endif
                                @if ($project->end_date)
                                    <span>{{ __('projects.end_date') }}: <strong class="font-medium text-ziifra-ink">{{ $project->end_date->format('M j, Y') }}</strong></span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @if ($canManage)
                    <div class="ziifra-project-detail-actions mt-5">
                        <a href="{{ route('projects.hours.export', ['project' => $project, 'month' => $selectedMonth]) }}" class="ziifra-btn-app-outline !text-sm">{{ __('daily_hours.export') }}</a>
                        <a href="{{ route('invoices.create', ['project' => $project->id, 'period_start' => $selectedMonth.'-01', 'period_end' => $monthCarbon->copy()->endOfMonth()->toDateString()]) }}" class="ziifra-btn-app-outline !text-sm">{{ __('daily_hours.generate_invoice') }}</a>
                        <a href="{{ route('projects.edit', $project) }}" class="ziifra-btn-app !text-sm" data-page-nav>{{ __('projects.edit') }}</a>
                    </div>
                @endif
            </div>

            <div class="ziifra-project-detail-progress-card">
                <div class="ziifra-project-detail-ring" style="background: conic-gradient(var(--color-ziifra-accent) {{ $completion }}%, rgb(226 232 240 / 0.55) 0)">
                    <div class="ziifra-project-detail-ring-inner">
                        <span class="text-2xl font-semibold tabular-nums text-ziifra-ink">{{ $completion }}%</span>
                        <span class="text-[0.65rem] font-medium text-ziifra-muted">{{ __('projects.progress') }}</span>
                    </div>
                </div>
                <p class="mt-3 text-center text-xs text-ziifra-muted">{{ trans_choice('projects.task_count', $taskCount, ['count' => $taskCount]) }}</p>
            </div>
        </div>
    </section>

    <div class="ziifra-project-detail-stats">
        <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'hours', 'month' => $selectedMonth, 'search' => $search]) }}" data-page-nav
            @class(['ziifra-project-detail-stat', 'ziifra-project-detail-stat-active' => $tab === 'hours', 'ziifra-project-detail-stat-warn' => $pendingHours > 0 && $tab !== 'hours'])>
            <span class="ziifra-project-detail-stat-label">{{ __('daily_hours.tab') }}</span>
            <span class="ziifra-project-detail-stat-value">{{ number_format($monthHours, 1) }}</span>
            <span class="ziifra-project-detail-stat-hint">{{ __('daily_hours.hours_this_month') }}</span>
        </a>
        <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'team']) }}" data-page-nav
            @class(['ziifra-project-detail-stat', 'ziifra-project-detail-stat-active' => $tab === 'team'])>
            <span class="ziifra-project-detail-stat-label">{{ __('projects.team') }}</span>
            <span class="ziifra-project-detail-stat-value">{{ $teamCount }}</span>
            <span class="ziifra-project-detail-stat-hint">{{ trans_choice('projects.team_count', $teamCount, ['count' => $teamCount]) }}</span>
        </a>
        <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'tasks']) }}" data-page-nav
            @class(['ziifra-project-detail-stat', 'ziifra-project-detail-stat-active' => $tab === 'tasks'])>
            <span class="ziifra-project-detail-stat-label">{{ __('projects.tasks') }}</span>
            <span class="ziifra-project-detail-stat-value">{{ $taskCount }}</span>
            <span class="ziifra-project-detail-stat-hint">{{ trans_choice('projects.milestone_count', $milestoneCount, ['count' => $milestoneCount]) }}</span>
        </a>
        @if ($pendingHours > 0)
            <div class="ziifra-project-detail-stat ziifra-project-detail-stat-warn">
                <span class="ziifra-project-detail-stat-label">{{ __('projects.pending_label') }}</span>
                <span class="ziifra-project-detail-stat-value">{{ $pendingHours }}</span>
                <span class="ziifra-project-detail-stat-hint">{{ trans_choice('projects.pending_hours', $pendingHours, ['count' => $pendingHours]) }}</span>
            </div>
        @endif
    </div>
    @endif

    <section @class(['ziifra-project-detail-workspace', 'ziifra-project-detail-workspace--hours' => $tab === 'hours'])>
        @if ($tab === 'hours')
            <nav class="ziifra-time-attendance-tabs" aria-label="{{ __('projects.title') }}">
                <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'hours', 'month' => $selectedMonth, 'search' => $search]) }}" data-page-nav
                    class="ziifra-time-attendance-tab ziifra-time-attendance-tab--active">
                    {{ __('daily_hours.tab') }}
                    @if ($pendingHours > 0)
                        <span class="ziifra-time-attendance-tab-badge">{{ $pendingHours }}</span>
                    @endif
                </a>
                <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'tasks']) }}" data-page-nav class="ziifra-time-attendance-tab">
                    {{ __('projects.tasks') }}
                </a>
                <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'team']) }}" data-page-nav class="ziifra-time-attendance-tab">
                    {{ __('projects.team') }}
                </a>
            </nav>
        @else
        <nav class="ziifra-project-detail-tabs" aria-label="{{ __('projects.title') }}">
            <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'hours', 'month' => $selectedMonth, 'search' => $search]) }}" data-page-nav
                @class(['ziifra-project-detail-tab', 'ziifra-project-detail-tab-active' => $tab === 'hours'])>
                {{ __('daily_hours.tab') }}
                @if ($pendingHours > 0)
                    <span class="ziifra-project-detail-tab-badge ziifra-project-detail-tab-badge-warn">{{ $pendingHours }}</span>
                @endif
            </a>
            <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'tasks']) }}" data-page-nav
                @class(['ziifra-project-detail-tab', 'ziifra-project-detail-tab-active' => $tab === 'tasks'])>
                {{ __('projects.tasks') }}
                @if ($taskCount > 0)
                    <span class="ziifra-project-detail-tab-badge">{{ $taskCount }}</span>
                @endif
            </a>
            <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'team']) }}" data-page-nav
                @class(['ziifra-project-detail-tab', 'ziifra-project-detail-tab-active' => $tab === 'team'])>
                {{ __('projects.team') }}
                @if ($teamCount > 0)
                    <span class="ziifra-project-detail-tab-badge">{{ $teamCount }}</span>
                @endif
            </a>
        </nav>
        @endif

        <div @class(['ziifra-project-detail-content', 'ziifra-project-detail-content--hours' => $tab === 'hours'])>
            @if ($tab === 'hours')
                @include('app.projects._hours-grid', compact('monthCarbon', 'prevMonth', 'nextMonth', 'isCurrentMonth'))
            @elseif ($tab === 'team')
                @include('app.projects._team')
            @else
                @include('app.projects._tasks', compact('milestoneCount'))
            @endif
        </div>
    </section>
</div>
@endsection

@if ($tab === 'hours' && $canManage)
    @push('scripts')
        <script>
            window.ziifraProjectHours = {
                upsertUrl: @json(route('projects.hours.upsert', $project)),
                csrf: @json(csrf_token()),
                standardDayHours: @json(\App\Services\DailyHoursService::STANDARD_DAY_HOURS),
                currency: @json($hoursGrid['currency'] ?? ($project->currency ?? 'EUR')),
            };
        </script>
        @vite('resources/js/project-hours-grid.js')
    @endpush
@endif
