@php
    use App\Enums\ProjectStatus;
    use App\Services\DailyHoursService;

    $currency = $hoursGrid['currency'] ?? ($project->currency ?? 'EUR');
    $projectedPayroll = $hoursGrid['totals']['payroll'] ?? 0;
    $pendingEmployees = $hoursGrid['totals']['pending_employees'] ?? 0;
    $employeeCount = $hoursGrid['employees']->count();
    $quickMonths = collect(range(0, 3))
        ->map(fn (int $offset) => $monthCarbon->copy()->subMonths(3 - $offset));

    $monthOptions = collect();
    $optionStart = ($project->start_date?->copy()->startOfMonth()) ?? $monthCarbon->copy()->subMonths(11);
    $optionCursor = $optionStart->copy();

    while ($optionCursor->lte(now()->startOfMonth())) {
        $monthOptions->push($optionCursor->copy());
        $optionCursor->addMonth();
    }

    if ($monthOptions->isEmpty()) {
        $monthOptions = collect([$monthCarbon->copy()]);
    }

    $statusTone = match ($project->status) {
        ProjectStatus::Active => 'active',
        ProjectStatus::Planning => 'planning',
        ProjectStatus::OnHold => 'hold',
        ProjectStatus::Completed => 'done',
        ProjectStatus::Cancelled => 'cancelled',
    };
@endphp

<section class="ziifra-time-attendance" data-time-attendance>
    <header class="ziifra-time-attendance-header">
        <nav class="ziifra-time-attendance-breadcrumb" aria-label="{{ __('projects.title') }}">
            <a href="{{ route('projects.index') }}" class="ziifra-time-attendance-breadcrumb-link" data-page-nav>{{ __('projects.title') }}</a>
            <span class="ziifra-time-attendance-breadcrumb-sep" aria-hidden="true">›</span>
            <span class="ziifra-time-attendance-breadcrumb-current">{{ $project->name }}</span>
        </nav>

        <div class="ziifra-time-attendance-title-row">
            <h2 class="ziifra-time-attendance-title">{{ $project->name }}</h2>
            <div class="ziifra-time-attendance-title-actions">
                @if ($canManage)
                    <a href="{{ route('projects.hours.export', ['project' => $project, 'month' => $selectedMonth]) }}" class="ziifra-time-attendance-btn-outline">
                        {{ __('daily_hours.export_excel') }}
                    </a>
                    <button type="button" class="ziifra-time-attendance-btn-primary" data-time-attendance-save hidden>
                        {{ __('daily_hours.save_changes') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="ziifra-time-attendance-stats">
            <span @class(['ziifra-time-attendance-stat-badge', 'ziifra-time-attendance-stat-badge--'.$statusTone])>{{ $project->status->label() }}</span>
            <span class="ziifra-time-attendance-stat">{{ trans_choice('daily_hours.stat_employees', $employeeCount, ['count' => $employeeCount]) }}</span>
            <span class="ziifra-time-attendance-stat">{{ __('daily_hours.stat_total_hours', ['hours' => number_format($hoursGrid['totals']['hours'], 0)]) }}</span>
            <span class="ziifra-time-attendance-stat">{{ __('daily_hours.stat_projected_payroll', ['amount' => number_format($projectedPayroll, 0), 'currency' => $currency]) }}</span>
            @if ($project->start_date)
                <span class="ziifra-time-attendance-stat">{{ __('daily_hours.stat_started', ['date' => $project->start_date->format('F Y')]) }}</span>
            @endif
        </div>
    </header>

    <div class="ziifra-time-attendance-toolbar">
        <form method="GET" action="{{ route('projects.show', $project) }}" class="ziifra-time-attendance-search" data-project-hours-filter>
            <input type="hidden" name="tab" value="hours">
            <input type="hidden" name="month" value="{{ $selectedMonth }}" data-project-hours-month>
            <label for="hours-search" class="sr-only">{{ __('daily_hours.search_employee') }}</label>
            <svg class="ziifra-time-attendance-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="search" id="hours-search" name="search" value="{{ $search }}" data-project-hours-search
                placeholder="{{ __('daily_hours.search_employee') }}" class="ziifra-time-attendance-search-input">
        </form>

        <div class="ziifra-time-attendance-month-controls">
            <form method="GET" action="{{ route('projects.show', $project) }}" class="ziifra-time-attendance-month-form" data-project-hours-filter>
                <input type="hidden" name="tab" value="hours">
                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <label for="hours-month" class="sr-only">{{ __('daily_hours.month') }}</label>
                <select id="hours-month" name="month" data-project-hours-month class="ziifra-time-attendance-month-select">
                    @foreach ($monthOptions as $optionMonth)
                        <option value="{{ $optionMonth->format('Y-m') }}" @selected($optionMonth->format('Y-m') === $selectedMonth)>
                            {{ __('daily_hours.month_label', ['month' => $optionMonth->format('F Y')]) }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="ziifra-time-attendance-month-chips" role="group" aria-label="{{ __('daily_hours.month') }}">
                @foreach ($quickMonths as $quickMonth)
                    @php $quickValue = $quickMonth->format('Y-m'); @endphp
                    <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'hours', 'month' => $quickValue, 'search' => $search ?: null]) }}"
                        @class(['ziifra-time-attendance-month-chip', 'ziifra-time-attendance-month-chip--active' => $quickValue === $selectedMonth])
                        data-page-nav>{{ $quickMonth->format('M') }}</a>
                @endforeach
            </div>
        </div>

        <div class="ziifra-time-attendance-legend">
            <span class="ziifra-time-attendance-legend-item">
                <span class="ziifra-time-attendance-legend-swatch ziifra-time-attendance-legend-swatch--approved"></span>
                {{ __('daily_hours.status_approved') }}
            </span>
            <span class="ziifra-time-attendance-legend-item">
                <span class="ziifra-time-attendance-legend-swatch ziifra-time-attendance-legend-swatch--overtime"></span>
                {{ __('daily_hours.status_overtime') }}
            </span>
        </div>
    </div>

    @if ($hoursGrid['employees']->isEmpty())
        <div class="ziifra-time-attendance-empty">
            <p class="font-medium">{{ __('daily_hours.no_employees') }}</p>
            <p class="mt-1 text-sm opacity-70">{{ __('daily_hours.no_employees_hint') }}</p>
            @if ($canManage)
                <a href="{{ route('projects.edit', $project) }}" class="ziifra-time-attendance-btn-primary mt-4 !inline-flex" data-page-nav>{{ __('projects.edit') }}</a>
            @endif
        </div>
    @else
        <div class="md:hidden" data-project-hours-grid>
            <div class="ziifra-time-attendance-mobile-list">
                @foreach ($hoursGrid['employees'] as $employee)
                    @include('app.projects._hours-employee-mobile', [
                        'employee' => $employee,
                        'hoursGrid' => $hoursGrid,
                        'selectedMonth' => $selectedMonth,
                        'canManage' => $canManage,
                        'project' => $project,
                        'currency' => $currency,
                    ])
                @endforeach
            </div>
        </div>

        <div class="ziifra-time-attendance-grid-wrap hidden md:block" data-project-hours-grid>
            <table class="ziifra-time-attendance-grid">
                <thead>
                    <tr>
                        <th class="ziifra-time-attendance-grid-sticky">{{ __('daily_hours.employee') }}</th>
                        <th class="ziifra-time-attendance-grid-rate">{{ __('daily_hours.rate_per_hour') }}</th>
                        @foreach ($hoursGrid['days'] as $day)
                            @php
                                $dayDate = \Carbon\Carbon::parse($selectedMonth.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                                $isToday = $isCurrentMonth && (int) $day === (int) now()->day;
                                $isWeekend = $dayDate->isWeekend();
                            @endphp
                            <th @class([
                                'ziifra-time-attendance-grid-day',
                                'ziifra-time-attendance-grid-day--today' => $isToday,
                                'ziifra-time-attendance-grid-day--weekend' => $isWeekend,
                            ])>
                                @if ($isToday)
                                    <span class="ziifra-time-attendance-grid-day-dot" aria-hidden="true"></span>
                                @endif
                                {{ $day }}
                            </th>
                        @endforeach
                        <th class="ziifra-time-attendance-grid-total">{{ __('daily_hours.total') }}</th>
                        <th class="ziifra-time-attendance-grid-pay">{{ __('daily_hours.pay', ['currency' => $currency]) }}</th>
                        <th class="ziifra-time-attendance-grid-status">{{ __('daily_hours.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hoursGrid['employees'] as $employee)
                        @php
                            $row = $hoursGrid['rows'][$employee->id] ?? ['hours' => 0, 'pay' => 0, 'rate' => 0, 'status' => 'empty'];
                            $rowRate = (float) $row['rate'];
                        @endphp
                        <tr data-employee-row="{{ $employee->id }}" data-hourly-rate="{{ $rowRate }}">
                            <td class="ziifra-time-attendance-grid-sticky">
                                <div class="ziifra-time-attendance-employee">
                                    <span class="ziifra-time-attendance-avatar" aria-hidden="true">{{ $employee->initials() }}</span>
                                    <span class="min-w-0">
                                        <a href="{{ route('employees.show', $employee) }}" class="ziifra-time-attendance-employee-name" data-page-nav>{{ $employee->fullName() }}</a>
                                        <span class="ziifra-time-attendance-employee-code">{{ $employee->displayCode() }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="ziifra-time-attendance-grid-rate tabular-nums">
                                {{ $rowRate > 0 ? $currency.' '.number_format($rowRate, 0) : '—' }}
                            </td>
                            @foreach ($hoursGrid['days'] as $day)
                                @php
                                    $entry = $hoursGrid['grid'][$employee->id][$day] ?? null;
                                    $value = $entry ? (float) $entry->hours : 0;
                                    $date = \Carbon\Carbon::parse($selectedMonth.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                                    $isToday = $isCurrentMonth && (int) $day === (int) now()->day;
                                    $isWeekend = $date->isWeekend();
                                    $isOvertime = $value > DailyHoursService::STANDARD_DAY_HOURS;
                                @endphp
                                <td @class([
                                    'ziifra-time-attendance-grid-cell',
                                    'ziifra-time-attendance-grid-cell--today' => $isToday,
                                    'ziifra-time-attendance-grid-cell--weekend' => $isWeekend,
                                ])>
                                    @if ($canManage)
                                        <input type="number" min="0" max="24" step="0.25"
                                            value="{{ $value > 0 ? $value : '' }}"
                                            data-employee-id="{{ $employee->id }}"
                                            data-work-date="{{ $date->toDateString() }}"
                                            @class([
                                                'ziifra-time-attendance-cell',
                                                'ziifra-time-attendance-cell--filled' => $value > 0 && ! $isOvertime,
                                                'ziifra-time-attendance-cell--overtime' => $value > 0 && $isOvertime,
                                            ])
                                            aria-label="{{ $employee->fullName() }} — {{ $date->format('M j') }}">
                                    @else
                                        <span @class([
                                            'ziifra-time-attendance-cell-readonly',
                                            'ziifra-time-attendance-cell--filled' => $value > 0 && ! $isOvertime,
                                            'ziifra-time-attendance-cell--overtime' => $value > 0 && $isOvertime,
                                        ])>{{ $value > 0 ? number_format($value, $value == floor($value) ? 0 : 1) : '' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="ziifra-time-attendance-grid-total tabular-nums" data-row-total>{{ number_format($row['hours'], 0) }}h</td>
                            <td class="ziifra-time-attendance-grid-pay tabular-nums" data-row-pay>{{ number_format($row['pay'], 0) }} {{ $currency }}</td>
                            <td class="ziifra-time-attendance-grid-status">
                                @if ($row['status'] === 'approved')
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--approved">{{ __('daily_hours.status_approved') }}</span>
                                @elseif ($row['status'] === 'pending')
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--pending">{{ __('daily_hours.status_pending') }}</span>
                                @else
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--empty">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <footer class="ziifra-time-attendance-footer">
            <div class="ziifra-time-attendance-footer-stats">
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value">{{ $employeeCount }}</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('daily_hours.footer_employees') }}</span>
                </div>
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value" data-footer-hours>{{ number_format($hoursGrid['totals']['hours'], 0) }}h</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('daily_hours.footer_hours') }}</span>
                </div>
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value" data-footer-payroll>{{ $currency }} {{ number_format($projectedPayroll, 0) }}</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('daily_hours.footer_payroll') }}</span>
                </div>
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value" data-footer-pending>{{ $pendingEmployees }}</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('daily_hours.footer_pending') }}</span>
                </div>
            </div>

            @if ($canManage)
                <div class="ziifra-time-attendance-footer-actions">
                    @if ($pendingEmployees > 0)
                        <form method="POST" action="{{ route('projects.hours.approve-all', $project) }}">
                            @csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <button type="submit" class="ziifra-time-attendance-btn-ghost">{{ __('daily_hours.approve_all') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('payroll.create', ['year' => $monthCarbon->year, 'month' => $monthCarbon->month]) }}"
                        class="ziifra-time-attendance-btn-outline ziifra-time-attendance-btn-outline--success" data-page-nav>
                        {{ __('daily_hours.generate_payroll') }}
                    </a>
                    <button type="button" class="ziifra-time-attendance-btn-primary" data-time-attendance-save hidden>
                        {{ __('daily_hours.save_all') }}
                    </button>
                </div>
            @endif
        </footer>
    @endif
</section>
