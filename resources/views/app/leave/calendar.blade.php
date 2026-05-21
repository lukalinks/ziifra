@extends('layouts.app')

@section('title', __('leave.calendar_title'))
@section('header', __('leave.calendar_title'))

@section('content')
@include('app.leave._nav')

@php
    $cal = $calendar;
    $queryBase = ['year' => $cal['year'], 'month' => $cal['month']];
    $pendingParam = $cal['showPending'] ? 1 : 0;
    $isCurrentMonth = $cal['year'] === (int) now()->year && $cal['month'] === (int) now()->month;
@endphp

<div class="ziifra-calendar">
    {{-- Toolbar --}}
    <div class="flex flex-col gap-4 border-b border-ziifra-line/80 bg-gradient-to-r from-ziifra-paper via-ziifra-cream/30 to-ziifra-paper p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
        <div class="flex items-center justify-center gap-2 sm:justify-start">
            <a href="{{ route('leave.calendar', array_merge($queryBase, ['year' => $cal['prev']['year'], 'month' => $cal['prev']['month'], 'pending' => $pendingParam])) }}"
                class="ziifra-btn-app-outline !rounded-full !px-3 !py-2"
                aria-label="Previous month">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="min-w-[11rem] text-center text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ $cal['monthLabel'] }}</h2>
            <a href="{{ route('leave.calendar', array_merge($queryBase, ['year' => $cal['next']['year'], 'month' => $cal['next']['month'], 'pending' => $pendingParam])) }}"
                class="ziifra-btn-app-outline !rounded-full !px-3 !py-2"
                aria-label="Next month">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-end">
            @unless ($isCurrentMonth)
                <a href="{{ route('leave.calendar', ['year' => now()->year, 'month' => now()->month, 'pending' => $pendingParam]) }}"
                    class="ziifra-btn-app-outline !rounded-full !text-xs">
                    {{ __('leave.calendar.today') }}
                </a>
            @endunless
            <form method="GET" action="{{ route('leave.calendar') }}" class="flex items-center">
                <input type="hidden" name="year" value="{{ $cal['year'] }}">
                <input type="hidden" name="month" value="{{ $cal['month'] }}">
                <label class="ziifra-calendar-legend-pill cursor-pointer select-none">
                    <input type="checkbox" name="pending" value="1" @checked($cal['showPending'])
                        onchange="this.form.submit()"
                        class="h-3.5 w-3.5 rounded border-ziifra-line text-ziifra-accent focus:ring-ziifra-accent/30">
                    <span>{{ __('leave.calendar.show_pending') }}</span>
                </label>
            </form>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-2 border-b border-ziifra-line/60 px-4 py-3 sm:px-6">
        <span class="ziifra-calendar-legend-pill">
            <span class="ziifra-calendar-chip-dot bg-emerald-500"></span>
            {{ __('leave.calendar.legend_approved') }}
        </span>
        <span class="ziifra-calendar-legend-pill">
            <span class="ziifra-calendar-chip-dot bg-amber-400"></span>
            {{ __('leave.calendar.legend_pending') }}
        </span>
        @if ($organization->observe_kosovo_holidays)
            <span class="ziifra-calendar-legend-pill">
                <span class="h-2 w-2 rounded-sm bg-sky-200 ring-1 ring-sky-400/40"></span>
                {{ __('leave.calendar.legend_holiday') }}
            </span>
        @endif
        <span class="ziifra-calendar-legend-pill">
            <span class="h-2 w-2 rounded-sm bg-ziifra-cream ring-1 ring-ziifra-line"></span>
            {{ __('leave.calendar.legend_non_workday') }}
        </span>
    </div>

    {{-- Grid --}}
    <div class="overflow-x-auto">
        <div class="min-w-[36rem] sm:min-w-0">
            <div class="ziifra-calendar-weekdays">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <div class="ziifra-calendar-weekday">{{ $weekday }}</div>
                @endforeach
            </div>

            @foreach ($cal['weeks'] as $week)
                <div class="ziifra-calendar-week">
                    @foreach ($week as $day)
                        @php
                            $isToday = $day['date']?->isToday() ?? false;
                            $entryCount = count($day['entries']);
                        @endphp
                        <div @class([
                            'ziifra-calendar-day',
                            ! $day['inMonth'] ? 'ziifra-calendar-day-out' : '',
                            $day['inMonth'] && ! $day['isWorkday'] && ! $day['holiday'] ? 'ziifra-calendar-day-weekend' : '',
                            $day['holiday'] ? 'ziifra-calendar-day-holiday' : '',
                            $isToday ? 'ziifra-calendar-day-today' : '',
                        ])>
                            @if ($day['date'])
                                <div class="flex items-start justify-between gap-1">
                                    <span @class([
                                        'ziifra-calendar-day-num',
                                        $isToday ? 'ziifra-calendar-day-num-today' : '',
                                        ! $day['inMonth'] ? 'ziifra-calendar-day-num-muted' : '',
                                    ])>
                                        {{ $day['date']->day }}
                                    </span>
                                </div>

                                @if ($day['holiday'])
                                    <p class="ziifra-calendar-holiday" title="{{ $day['holiday'] }}">{{ $day['holiday'] }}</p>
                                @endif

                                <div class="mt-auto flex flex-col gap-1 pt-1.5">
                                    @foreach (array_slice($day['entries'], 0, 3) as $entry)
                                        @php
                                            $initial = mb_strtoupper(mb_substr($entry['employeeName'], 0, 1));
                                            $isApproved = $entry['status'] === \App\Enums\LeaveRequestStatus::Approved;
                                        @endphp
                                        <a href="{{ route('leave.show', $entry['id']) }}"
                                            @class([
                                                'ziifra-calendar-chip',
                                                $isApproved ? 'ziifra-calendar-chip-approved' : 'ziifra-calendar-chip-pending',
                                            ])
                                            title="{{ $entry['employeeName'] }} — {{ $entry['leaveType'] }}">
                                            <span @class([
                                                'ziifra-calendar-chip-dot',
                                                $isApproved ? 'bg-emerald-500' : 'bg-amber-400',
                                            ])></span>
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-ziifra-paper/80 text-[9px] font-bold ring-1 ring-black/5">
                                                {{ $initial }}
                                            </span>
                                            <span class="min-w-0 truncate">{{ $entry['employeeName'] }}</span>
                                        </a>
                                    @endforeach
                                    @if ($entryCount > 3)
                                        <p class="ziifra-calendar-more">
                                            {{ __('leave.calendar.more', ['count' => $entryCount - 3]) }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

<p class="mt-5 text-center text-xs text-ziifra-muted sm:text-left">
    {{ __('leave.calendar.hint') }}
</p>
@endsection
