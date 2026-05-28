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
    $documentCount = $project->documents->count();
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

    $tabs = [
        ['id' => 'hours', 'label' => __('daily_hours.tab'), 'badge' => $pendingHours > 0 ? $pendingHours : null, 'warn' => $pendingHours > 0],
        ['id' => 'tasks', 'label' => __('projects.tasks'), 'badge' => $taskCount > 0 ? $taskCount : null, 'warn' => false],
        ['id' => 'documents', 'label' => __('projects.documents_tab'), 'badge' => $documentCount > 0 ? $documentCount : null, 'warn' => false],
        ['id' => 'team', 'label' => __('projects.team'), 'badge' => $teamCount > 0 ? $teamCount : null, 'warn' => false],
    ];

    $tabQuery = ['project' => $project];
@endphp

<div class="ziifra-project-detail">
    <a href="{{ route('projects.index') }}" class="ziifra-project-detail-back" data-page-nav>
        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('projects.back_to_list') }}
    </a>

    <header class="ziifra-project-detail-top">
        <div class="ziifra-project-detail-top-main">
            <span class="ziifra-project-detail-icon" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.25-3.75l3 3m0 0l3-3m-3 3V2.25"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h1 class="text-lg font-semibold tracking-tight text-ziifra-ink">{{ $project->name }}</h1>
                    <span @class(['ziifra-project-detail-status', 'ziifra-project-detail-status-'.$statusTone])>{{ $project->status->label() }}</span>
                    @if ($project->budget)
                        <span class="ziifra-project-detail-meta">{{ number_format((float) $project->budget, 0) }} {{ $project->currency ?? 'EUR' }}</span>
                    @endif
                    <span class="ziifra-project-detail-meta">{{ $completion }}% · {{ trans_choice('projects.task_count', $taskCount, ['count' => $taskCount]) }}</span>
                </div>
                @php
                    $subline = collect([
                        $project->description ? \Illuminate\Support\Str::limit($project->description, 72) : null,
                        $project->start_date ? __('projects.start_date').' '.$project->start_date->format('M j, Y') : null,
                        $project->end_date ? __('projects.end_date').' '.$project->end_date->format('M j, Y') : null,
                    ])->filter()->implode(' · ');
                @endphp
                @if ($subline !== '')
                    <p class="ziifra-project-detail-subline">{{ $subline }}</p>
                @endif
            </div>
        </div>
        @if ($canManage)
            <div class="ziifra-project-detail-top-actions">
                <a href="{{ route('projects.hours.export', ['project' => $project, 'month' => $selectedMonth]) }}" class="ziifra-project-detail-action">{{ __('daily_hours.export') }}</a>
                <a href="{{ route('invoices.create', ['project' => $project->id, 'period_start' => $selectedMonth.'-01', 'period_end' => $monthCarbon->copy()->endOfMonth()->toDateString()]) }}" class="ziifra-project-detail-action">{{ __('daily_hours.generate_invoice') }}</a>
                <a href="{{ route('projects.edit', $project) }}" class="ziifra-project-detail-action ziifra-project-detail-action--primary" data-page-nav>{{ __('projects.edit') }}</a>
            </div>
        @endif
    </header>

    <div class="ziifra-project-detail-kpis">
        <span @class(['ziifra-project-detail-kpi', 'ziifra-project-detail-kpi--warn' => $pendingHours > 0])>
            <span class="ziifra-project-detail-kpi-value">{{ number_format($monthHours, 1) }}</span>
            <span class="ziifra-project-detail-kpi-label">{{ __('daily_hours.tab') }}</span>
        </span>
        <span class="ziifra-project-detail-kpi">
            <span class="ziifra-project-detail-kpi-value">{{ $teamCount }}</span>
            <span class="ziifra-project-detail-kpi-label">{{ __('projects.team') }}</span>
        </span>
        <span class="ziifra-project-detail-kpi">
            <span class="ziifra-project-detail-kpi-value">{{ $taskCount }}</span>
            <span class="ziifra-project-detail-kpi-label">{{ __('projects.tasks') }}</span>
        </span>
        <span class="ziifra-project-detail-kpi">
            <span class="ziifra-project-detail-kpi-value">{{ $documentCount }}</span>
            <span class="ziifra-project-detail-kpi-label">{{ __('projects.documents_tab') }}</span>
        </span>
    </div>

    <section @class(['ziifra-project-detail-workspace', 'ziifra-project-detail-workspace--hours' => $tab === 'hours'])>
        <nav class="ziifra-project-detail-tabs" aria-label="{{ __('projects.title') }}">
            @foreach ($tabs as $t)
                @php
                    $params = $tabQuery + ['tab' => $t['id']];
                    if ($t['id'] === 'hours') {
                        $params += ['month' => $selectedMonth, 'search' => $search ?: null];
                    }
                @endphp
                <a href="{{ route('projects.show', $params) }}" data-page-nav
                    @class(['ziifra-project-detail-tab', 'ziifra-project-detail-tab-active' => $tab === $t['id']])>
                    {{ $t['label'] }}
                    @if ($t['badge'])
                        <span @class(['ziifra-project-detail-tab-badge', 'ziifra-project-detail-tab-badge-warn' => $t['warn']])>{{ $t['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div @class(['ziifra-project-detail-content', 'ziifra-project-detail-content--hours' => $tab === 'hours'])>
            @if ($tab === 'hours')
                @include('app.projects._hours-chart', compact('hoursChart', 'chartYear', 'chartMonth'))
                @include('app.projects._hours-grid', compact('monthCarbon', 'prevMonth', 'nextMonth', 'isCurrentMonth'))
            @elseif ($tab === 'team')
                @include('app.projects._team')
            @elseif ($tab === 'documents')
                @include('app.projects._documents')
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
