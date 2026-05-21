@extends('layouts.app')

@section('title', __('time.title'))
@section('header', __('time.title'))

@section('content')
@php
    $formatMinutes = fn (int $minutes) => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    $queryBase = array_filter([
        'week' => $weekStart->toDateString(),
        'employee_id' => $selectedEmployeeId,
    ]);
@endphp

<div class="relative mb-8 overflow-hidden rounded-2xl border border-ziifra-line/80 bg-gradient-to-br from-ziifra-paper via-ziifra-paper to-ziifra-cream/50 p-6 shadow-sm sm:p-8">
    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-ziifra-accent/10 blur-3xl"></div>
    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-2xl">
            <p class="text-base text-ziifra-ink leading-relaxed">{{ __('time.subtitle') }}</p>
            <p class="mt-3 text-sm font-semibold text-ziifra-ink">
                {{ __('time.period_label', [
                    'start' => $weekStart->format('M j, Y'),
                    'end' => $weekEnd->format('M j, Y'),
                ]) }}
            </p>
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('time.standard_day', ['hours' => $standardHours]) }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @can('create', \App\Models\TimeEntry::class)
                <a href="{{ route('time.create', $queryBase) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-ziifra-line bg-ziifra-paper px-4 py-2.5 text-sm font-semibold text-ziifra-ink shadow-sm transition hover:bg-ziifra-cream">
                    {{ __('time.add_entry') }}
                </a>
            @endcan
            <a href="{{ route('time.export', $queryBase) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-ziifra-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ziifra-accent-deep">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('time.export_csv') }}
            </a>
        </div>
    </div>
</div>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('time.index', array_filter(['week' => $prevWeek, 'employee_id' => $selectedEmployeeId])) }}"
            class="rounded-lg border border-ziifra-line bg-ziifra-paper px-3 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            ← {{ __('time.prev_week') }}
        </a>
        <a href="{{ route('time.index', array_filter(['employee_id' => $selectedEmployeeId])) }}"
            class="rounded-lg border border-ziifra-line bg-ziifra-paper px-3 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            {{ __('time.this_week') }}
        </a>
        <a href="{{ route('time.index', array_filter(['week' => $nextWeek, 'employee_id' => $selectedEmployeeId])) }}"
            class="rounded-lg border border-ziifra-line bg-ziifra-paper px-3 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            {{ __('time.next_week') }} →
        </a>
    </div>

    @if ($employees->count() > 1)
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <select name="employee_id" class="rounded-lg border border-ziifra-line bg-ziifra-paper px-3 py-2 text-sm">
                <option value="">{{ __('time.all_employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)>{{ $employee->fullName() }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-ziifra-line bg-ziifra-paper px-4 py-2 text-sm font-medium hover:bg-ziifra-cream">{{ __('time.filter') }}</button>
        </form>
    @endif
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('time.total_hours') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $formatMinutes($summary['total_minutes']) }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('time.regular_hours') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $formatMinutes($summary['regular_minutes']) }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('time.overtime_hours') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $formatMinutes($summary['overtime_minutes']) }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('time.break_total') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $summary['break_minutes'] }}<span class="text-lg font-medium text-ziifra-muted">m</span></p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('time.days_worked') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $summary['days_worked'] }}</p>
    </div>
</div>

@if ($canClock)
    <div class="mb-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('time.clock_panel') }}</h2>

        @if ($linkedEmployee && $openEntry)
            <p class="mt-2 text-sm text-ziifra-ink">
                {{ __('time.currently_in', ['time' => $openEntry->clock_in->format('H:i')]) }}
            </p>
        @elseif ($linkedEmployee)
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('time.not_clocked_in') }}</p>
        @endif

        @if ($todayTotals)
            <p class="mt-2 text-sm">{{ __('time.today_hours', ['hours' => $formatMinutes($todayTotals['total_minutes'])]) }}</p>
            @if ($todayTotals['overtime_minutes'] > 0)
                <p class="text-sm text-amber-800">{{ __('time.overtime_today', ['hours' => $formatMinutes($todayTotals['overtime_minutes'])]) }}</p>
            @endif
        @endif

        <div class="mt-4 flex flex-col gap-4 xl:flex-row xl:items-start">
            @if ($canClockForOthers)
                <form method="POST" action="{{ route('time.clock-in') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    @if ($selectedEmployeeId)
                        <input type="hidden" name="employee_id" value="{{ $selectedEmployeeId }}">
                    @endif
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.clock_for') }}</label>
                        <select name="employee_id" required class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                            <option value="">{{ __('time.select_employee') }}</option>
                            @foreach ($clockableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)>{{ $employee->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="ziifra-btn-primary">{{ __('time.clock_in') }}</button>
                </form>
                <form method="POST" action="{{ route('time.clock-out') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.clock_for') }}</label>
                        <select name="employee_id" required class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                            <option value="">{{ __('time.select_employee') }}</option>
                            @foreach ($clockableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)>{{ $employee->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.break') }}</label>
                        <input type="number" name="break_minutes" min="0" max="480" value="0" class="w-24 rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    </div>
                    <div class="min-w-[12rem]">
                        <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.notes') }}</label>
                        <input type="text" name="notes" maxlength="2000" class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="ziifra-btn-app-outline">{{ __('time.clock_out') }}</button>
                </form>
            @else
                @if ($openEntry)
                    <form method="POST" action="{{ route('time.clock-out') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.break') }}</label>
                            <input type="number" name="break_minutes" min="0" max="480" value="0" class="w-24 rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        </div>
                        <div class="min-w-[14rem]">
                            <label class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('time.notes') }}</label>
                            <input type="text" name="notes" maxlength="2000" placeholder="{{ __('time.notes_placeholder') }}" class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="ziifra-btn-app-outline">{{ __('time.clock_out') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('time.clock-in') }}">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <button type="submit" class="ziifra-btn-primary">{{ __('time.clock_in') }}</button>
                    </form>
                @endif
            @endif
        </div>
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper shadow-sm">
    <div class="border-b border-ziifra-line/60 bg-ziifra-cream/40 px-5 py-4">
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('time.timesheet') }}</h2>
        <p class="mt-1 text-xs text-ziifra-muted">{{ __('time.timesheet_hint', ['count' => $summary['entry_count']]) }}</p>
    </div>

    @if ($entries->isEmpty())
        <p class="p-10 text-center text-sm text-ziifra-muted">{{ __('time.empty_week') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ziifra-line/60 text-sm">
                <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                    <tr>
                        @if ($showEmployeeColumn)
                            <th class="px-4 py-3">{{ __('time.employee') }}</th>
                        @endif
                        <th class="px-4 py-3">{{ __('time.date') }}</th>
                        <th class="px-4 py-3">{{ __('time.clock_in') }}</th>
                        <th class="px-4 py-3">{{ __('time.clock_out') }}</th>
                        <th class="px-4 py-3">{{ __('time.break') }}</th>
                        <th class="px-4 py-3">{{ __('time.hours_worked') }}</th>
                        <th class="px-4 py-3">{{ __('time.notes') }}</th>
                        <th class="px-4 py-3">{{ __('time.status') }}</th>
                        @if ($canManageEntries)
                            <th class="px-4 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-ziifra-line/60">
                    @foreach ($entriesByDate as $date => $dayEntries)
                        @php
                            $dayMinutes = $dayEntries
                                ->filter(fn ($entry) => ! $entry->isOpen())
                                ->sum(fn ($entry) => $entry->workedMinutes() ?? 0);
                            $dayLabel = \Carbon\Carbon::parse($date)->format('l, M j');
                        @endphp
                        <tr class="bg-ziifra-cream/30">
                            <td colspan="{{ 7 + ($showEmployeeColumn ? 1 : 0) + ($canManageEntries ? 1 : 0) }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                                {{ $dayLabel }}
                                <span class="ml-2 font-normal normal-case text-ziifra-ink">{{ __('time.day_total', ['hours' => $formatMinutes($dayMinutes)]) }}</span>
                            </td>
                        </tr>
                        @foreach ($dayEntries as $entry)
                            <tr class="hover:bg-ziifra-cream/20">
                                @if ($showEmployeeColumn)
                                    <td class="px-4 py-3 font-medium text-ziifra-ink">{{ $entry->employee->fullName() }}</td>
                                @endif
                                <td class="px-4 py-3 text-ziifra-muted">{{ $entry->clock_in->format('M j') }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $entry->clock_in->format('H:i') }}</td>
                                <td class="px-4 py-3 tabular-nums text-ziifra-muted">
                                    {{ $entry->clock_out ? $entry->clock_out->format('H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-ziifra-muted">{{ $entry->break_minutes }}</td>
                                <td class="px-4 py-3 tabular-nums font-medium">{{ $entry->workedHoursLabel() }}</td>
                                <td class="max-w-xs truncate px-4 py-3 text-ziifra-muted" title="{{ $entry->notes }}">{{ $entry->notes ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-800' => ! $entry->isOpen(),
                                        'bg-amber-50 text-amber-900' => $entry->isOpen(),
                                    ])>{{ $entry->isOpen() ? __('time.open') : __('time.closed') }}</span>
                                </td>
                                @can('update', $entry)
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('time.entries.edit', ['timeEntry' => $entry, 'week' => $weekStart->toDateString()]) }}"
                                            class="text-xs font-semibold text-ziifra-accent-deep hover:underline">{{ __('time.edit') }}</a>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot class="bg-ziifra-cream/50 text-sm font-semibold text-ziifra-ink">
                    <tr>
                        <td colspan="{{ 4 + ($showEmployeeColumn ? 1 : 0) }}" class="px-4 py-3 text-right uppercase tracking-wide text-xs text-ziifra-muted">{{ __('time.week_total') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $summary['break_minutes'] }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $formatMinutes($summary['total_minutes']) }}</td>
                        <td colspan="{{ 2 + ($canManageEntries ? 1 : 0) }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
