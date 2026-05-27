@extends('layouts.app')

@section('title', __('time.title'))
@section('header', __('time.title'))

@section('content')
@php
    $formatMinutes = fn (int $minutes) => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    $employeeQuery = fn ($employee = null) => array_filter([
        'week' => $weekStart->toDateString(),
        'employee' => ($employee ?? $selectedEmployee)?->employee_code,
    ]);
@endphp

<div class="ziifra-dashboard-page ziifra-time-page">
    <section class="ziifra-time-toolbar">
        <div class="ziifra-time-toolbar-main">
            <div class="min-w-0">
                <p class="ziifra-time-period">{{ __('time.period_label', [
                    'start' => $weekStart->format('M j'),
                    'end' => $weekEnd->format('M j, Y'),
                ]) }}</p>
                @if ($selectedEmployee)
                    <p class="ziifra-time-filter-label">{{ $selectedEmployee->fullName() }} · {{ $selectedEmployee->displayCode() }}</p>
                @endif
            </div>

            <div class="ziifra-time-week-nav">
                <a href="{{ route('time.index', $employeeQuery($selectedEmployee) + ['week' => $prevWeek]) }}" class="ziifra-time-week-btn" data-page-nav aria-label="{{ __('time.prev_week') }}">←</a>
                <a href="{{ route('time.index', $employeeQuery($selectedEmployee)) }}" class="ziifra-time-week-btn" data-page-nav>{{ __('time.this_week') }}</a>
                <a href="{{ route('time.index', $employeeQuery($selectedEmployee) + ['week' => $nextWeek]) }}" class="ziifra-time-week-btn" data-page-nav aria-label="{{ __('time.next_week') }}">→</a>
            </div>
        </div>

        <div class="ziifra-time-toolbar-actions">
            @if ($employees->count() > 1)
                <form method="GET" action="{{ route('time.index') }}" class="ziifra-time-employee-filter">
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <label for="time-employee" class="sr-only">{{ __('time.employee') }}</label>
                    <select id="time-employee" name="employee" class="ziifra-time-select" onchange="this.form.submit()">
                        <option value="">{{ __('time.all_employees') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->employee_code }}" @selected($selectedEmployee?->is($employee))>{{ $employee->fullName() }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @can('create', \App\Models\TimeEntry::class)
                <a href="{{ route('time.create', $indexQuery) }}" class="ziifra-btn-app-outline !py-2 !text-sm" data-page-nav>{{ __('time.add_entry') }}</a>
            @endcan
            <a href="{{ route('time.export', $indexQuery) }}" class="ziifra-btn-app !py-2 !text-sm">{{ __('time.export_csv') }}</a>
        </div>
    </section>

    <div class="ziifra-time-stats">
        <div class="ziifra-time-stat">
            <span class="ziifra-time-stat-value">{{ $formatMinutes($summary['total_minutes']) }}</span>
            <span class="ziifra-time-stat-label">{{ __('time.total_hours') }}</span>
        </div>
        <div class="ziifra-time-stat">
            <span class="ziifra-time-stat-value">{{ $formatMinutes($summary['regular_minutes']) }}</span>
            <span class="ziifra-time-stat-label">{{ __('time.regular_hours') }}</span>
        </div>
        <div class="ziifra-time-stat">
            <span class="ziifra-time-stat-value">{{ $formatMinutes($summary['overtime_minutes']) }}</span>
            <span class="ziifra-time-stat-label">{{ __('time.overtime_hours') }}</span>
        </div>
        <div class="ziifra-time-stat">
            <span class="ziifra-time-stat-value">{{ $summary['break_minutes'] }}m</span>
            <span class="ziifra-time-stat-label">{{ __('time.break_total') }}</span>
        </div>
        <div class="ziifra-time-stat">
            <span class="ziifra-time-stat-value">{{ $summary['days_worked'] }}</span>
            <span class="ziifra-time-stat-label">{{ __('time.days_worked') }}</span>
        </div>
    </div>

    @if ($canClock)
        <section class="ziifra-time-clock-bar">
            <div class="ziifra-time-clock-status">
                @if ($linkedEmployee && $openEntry)
                    <span class="ziifra-time-clock-dot ziifra-time-clock-dot--active"></span>
                    <span class="text-sm">{{ __('time.currently_in', ['time' => $openEntry->clock_in->format('H:i')]) }}</span>
                @else
                    <span class="ziifra-time-clock-dot"></span>
                    <span class="text-sm text-ziifra-muted">{{ __('time.not_clocked_in') }}</span>
                @endif
                @if ($todayTotals)
                    <span class="text-xs text-ziifra-muted">· {{ __('time.today_hours', ['hours' => $formatMinutes($todayTotals['total_minutes'])]) }}</span>
                @endif
            </div>

            <div class="ziifra-time-clock-actions">
                @if ($canClockForOthers)
                    <form method="POST" action="{{ route('time.clock-in') }}" class="ziifra-time-clock-form">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        @if ($selectedEmployee)
                            <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                        @endif
                        <select name="employee_id" required class="ziifra-time-select !py-1.5 !text-xs">
                            <option value="">{{ __('time.select_employee') }}</option>
                            @foreach ($clockableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedEmployee?->is($employee))>{{ $employee->fullName() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ziifra-btn-app !py-1.5 !text-xs">{{ __('time.clock_in') }}</button>
                    </form>
                    <form method="POST" action="{{ route('time.clock-out') }}" class="ziifra-time-clock-form">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <select name="employee_id" required class="ziifra-time-select !py-1.5 !text-xs">
                            <option value="">{{ __('time.select_employee') }}</option>
                            @foreach ($clockableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedEmployee?->is($employee))>{{ $employee->fullName() }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="break_minutes" min="0" max="480" value="0" class="ziifra-time-input-num" aria-label="{{ __('time.break') }}">
                        <button type="submit" class="ziifra-btn-app-outline !py-1.5 !text-xs">{{ __('time.clock_out') }}</button>
                    </form>
                @else
                    @if ($openEntry)
                        <form method="POST" action="{{ route('time.clock-out') }}" class="ziifra-time-clock-form">
                            @csrf
                            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                            <input type="number" name="break_minutes" min="0" max="480" value="0" class="ziifra-time-input-num" aria-label="{{ __('time.break') }}">
                            <input type="text" name="notes" maxlength="2000" placeholder="{{ __('time.notes_placeholder') }}" class="ziifra-time-input-notes">
                            <button type="submit" class="ziifra-btn-app-outline !py-1.5 !text-xs">{{ __('time.clock_out') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('time.clock-in') }}">
                            @csrf
                            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                            <button type="submit" class="ziifra-btn-app !py-1.5 !text-xs">{{ __('time.clock_in') }}</button>
                        </form>
                    @endif
                @endif
            </div>
        </section>
    @endif

    <section class="ziifra-index-panel ziifra-time-sheet">
        <div class="ziifra-time-sheet-head">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('time.timesheet') }}</h2>
            <p class="text-xs text-ziifra-muted">{{ __('time.timesheet_hint', ['count' => $summary['entry_count']]) }}</p>
        </div>

        @if ($entries->isEmpty())
            <p class="ziifra-time-empty">{{ __('time.empty_week') }}</p>
        @else
            <div class="space-y-3 p-3 md:hidden">
                @foreach ($entriesByDate as $date => $dayEntries)
                    @php
                        $dayMinutes = $dayEntries->filter(fn ($entry) => ! $entry->isOpen())->sum(fn ($entry) => $entry->workedMinutes() ?? 0);
                    @endphp
                    <div>
                        <div class="ziifra-time-day-head">
                            <span>{{ \Carbon\Carbon::parse($date)->format('D, M j') }}</span>
                            <span>{{ $formatMinutes($dayMinutes) }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach ($dayEntries as $entry)
                                <article class="ziifra-time-entry-card">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($showEmployeeColumn)
                                                <p class="text-[0.65rem] font-medium text-ziifra-muted">{{ $entry->employee->fullName() }}</p>
                                            @endif
                                            <p class="font-semibold tabular-nums">{{ $entry->clock_in->format('H:i') }} – {{ $entry->clock_out?->format('H:i') ?? '—' }}</p>
                                            <p class="mt-0.5 text-xs text-ziifra-muted">{{ $entry->workedHoursLabel() }} · {{ $entry->break_minutes }}m</p>
                                        </div>
                                        <span @class(['ziifra-time-badge', 'ziifra-time-badge--open' => $entry->isOpen()])>{{ $entry->isOpen() ? __('time.open') : __('time.closed') }}</span>
                                    </div>
                                    @can('update', $entry)
                                        <a href="{{ route('time.entries.edit', ['timeEntry' => $entry, 'week' => $weekStart->toDateString()]) }}" class="mt-2 inline-block text-xs font-medium text-ziifra-accent-deep hover:underline">{{ __('time.edit') }}</a>
                                    @endcan
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="ziifra-table-scroll hidden md:block">
                <table class="ziifra-time-table min-w-full text-sm">
                    <thead>
                        <tr>
                            @if ($showEmployeeColumn)
                                <th>{{ __('time.employee') }}</th>
                            @endif
                            <th>{{ __('time.date') }}</th>
                            <th>{{ __('time.clock_in') }}</th>
                            <th>{{ __('time.clock_out') }}</th>
                            <th>{{ __('time.break') }}</th>
                            <th>{{ __('time.hours_worked') }}</th>
                            <th>{{ __('time.status') }}</th>
                            @if ($canManageEntries)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entriesByDate as $date => $dayEntries)
                            @php
                                $dayMinutes = $dayEntries->filter(fn ($entry) => ! $entry->isOpen())->sum(fn ($entry) => $entry->workedMinutes() ?? 0);
                            @endphp
                            <tr class="ziifra-time-table-day">
                                <td colspan="{{ 6 + ($showEmployeeColumn ? 1 : 0) + ($canManageEntries ? 1 : 0) }}">
                                    {{ \Carbon\Carbon::parse($date)->format('l, M j') }}
                                    <span class="ml-2 font-normal normal-case tabular-nums text-ziifra-ink">{{ $formatMinutes($dayMinutes) }}</span>
                                </td>
                            </tr>
                            @foreach ($dayEntries as $entry)
                                <tr>
                                    @if ($showEmployeeColumn)
                                        <td class="font-medium">{{ $entry->employee->fullName() }}</td>
                                    @endif
                                    <td class="text-ziifra-muted">{{ $entry->clock_in->format('M j') }}</td>
                                    <td class="tabular-nums">{{ $entry->clock_in->format('H:i') }}</td>
                                    <td class="tabular-nums text-ziifra-muted">{{ $entry->clock_out?->format('H:i') ?? '—' }}</td>
                                    <td class="tabular-nums text-ziifra-muted">{{ $entry->break_minutes }}</td>
                                    <td class="tabular-nums font-medium">{{ $entry->workedHoursLabel() }}</td>
                                    <td>
                                        <span @class(['ziifra-time-badge', 'ziifra-time-badge--open' => $entry->isOpen()])>{{ $entry->isOpen() ? __('time.open') : __('time.closed') }}</span>
                                    </td>
                                    @can('update', $entry)
                                        <td class="text-right">
                                            <a href="{{ route('time.entries.edit', ['timeEntry' => $entry, 'week' => $weekStart->toDateString()]) }}" class="text-xs font-medium text-ziifra-accent-deep hover:underline">{{ __('time.edit') }}</a>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="{{ 4 + ($showEmployeeColumn ? 1 : 0) }}" class="text-right text-xs uppercase tracking-wide text-ziifra-muted">{{ __('time.week_total') }}</td>
                            <td class="tabular-nums">{{ $summary['break_minutes'] }}</td>
                            <td class="tabular-nums font-semibold">{{ $formatMinutes($summary['total_minutes']) }}</td>
                            <td colspan="{{ 2 + ($canManageEntries ? 1 : 0) }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
