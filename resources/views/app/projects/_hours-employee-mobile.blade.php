@php
    use App\Services\DailyHoursService;

    $row = $hoursGrid['rows'][$employee->id] ?? ['hours' => 0, 'pay' => 0, 'rate' => 0, 'status' => 'empty'];
    $rowRate = (float) $row['rate'];
    $currency = $currency ?? ($hoursGrid['currency'] ?? 'EUR');
@endphp

<article class="ziifra-time-attendance-mobile-card">
    <div class="ziifra-time-attendance-mobile-head">
        <a href="{{ route('employees.show', $employee) }}" class="ziifra-time-attendance-mobile-employee" data-page-nav>
            <span class="ziifra-time-attendance-avatar" aria-hidden="true">{{ $employee->initials() }}</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold">{{ $employee->fullName() }}</span>
                <span class="block truncate text-xs opacity-70">{{ $employee->displayCode() }}</span>
            </span>
        </a>
        <div class="text-right">
            <span class="block text-lg font-semibold tabular-nums">{{ number_format($row['hours'], 1) }}h</span>
            <span class="text-[0.65rem] opacity-70">{{ number_format($row['pay'], 0) }} {{ $currency }}</span>
        </div>
    </div>

    @if ($rowRate > 0)
        <p class="ziifra-time-attendance-mobile-rate">{{ __('daily_hours.rate_per_hour') }}: {{ $currency }} {{ number_format($rowRate, 0) }}</p>
    @endif

    <div class="ziifra-time-attendance-mobile-days">
        @foreach ($hoursGrid['days'] as $day)
            @php
                $entry = $hoursGrid['grid'][$employee->id][$day] ?? null;
                $value = $entry ? (float) $entry->hours : 0;
                $date = \Carbon\Carbon::parse($selectedMonth.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                $isToday = $selectedMonth === now()->format('Y-m') && (int) $day === (int) now()->day;
                $isOvertime = $value > DailyHoursService::STANDARD_DAY_HOURS;
            @endphp
            <div @class([
                'ziifra-time-attendance-mobile-day',
                'ziifra-time-attendance-mobile-day--today' => $isToday,
            ])>
                <span class="ziifra-time-attendance-mobile-day-label">{{ $day }}</span>
                @if ($canManage)
                    <input type="number" min="0" max="24" step="0.25"
                        value="{{ $value > 0 ? $value : '' }}"
                        data-employee-id="{{ $employee->id }}"
                        data-work-date="{{ $date->toDateString() }}"
                        @class([
                            'ziifra-time-attendance-cell ziifra-time-attendance-cell-mobile',
                            'ziifra-time-attendance-cell--filled' => $value > 0 && ! $isOvertime,
                            'ziifra-time-attendance-cell--overtime' => $value > 0 && $isOvertime,
                        ])
                        aria-label="{{ $employee->fullName() }} — {{ $date->format('M j') }}">
                @else
                    <span @class([
                        'ziifra-time-attendance-mobile-day-value',
                        'ziifra-time-attendance-cell--filled' => $value > 0 && ! $isOvertime,
                        'ziifra-time-attendance-cell--overtime' => $value > 0 && $isOvertime,
                    ])>{{ $value > 0 ? number_format($value, 1) : '—' }}</span>
                @endif
            </div>
        @endforeach
    </div>

    <div class="ziifra-time-attendance-mobile-foot">
        @if ($row['status'] === 'approved')
            <span class="ziifra-time-attendance-status ziifra-time-attendance-status--approved">{{ __('daily_hours.status_approved') }}</span>
        @elseif ($row['status'] === 'pending')
            <span class="ziifra-time-attendance-status ziifra-time-attendance-status--pending">{{ __('daily_hours.status_pending') }}</span>
        @endif

        @if ($canManage && $row['status'] === 'pending')
            <form method="POST" action="{{ route('projects.hours.approve', [$project, $employee]) }}">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <button type="submit" class="ziifra-time-attendance-btn-ghost !text-xs">{{ __('daily_hours.approve_row') }}</button>
            </form>
        @endif
    </div>
</article>
