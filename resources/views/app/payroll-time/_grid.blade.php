@php
    $monthAll = $monthAll ?? false;
    $monthCarbon = $monthAll ? now() : \Carbon\Carbon::create($year, $month, 1);
    $isCurrentMonth = ! $monthAll && $monthCarbon->isSameMonth(now());
    $currency = $organization->currency ?? 'EUR';
    $quickMonths = collect(range(0, 3))->map(fn (int $offset) => $monthCarbon->copy()->subMonths(3 - $offset));
    $filterParams = fn (array $extra = []) => array_filter(array_merge([
        'organization' => $organization,
        'year' => $year,
        'month' => $monthAll ? 'all' : $month,
        'project_id' => request('project_id'),
        'search' => $search ?: null,
    ], $extra));
@endphp

<section class="ziifra-time-attendance" data-payroll-time-grid>
    <div class="ziifra-time-attendance-toolbar">
        <form method="GET" class="ziifra-time-attendance-search" data-payroll-time-filter>
            @if (request()->has('project_id'))
                <input type="hidden" name="project_id" value="{{ request('project_id') }}">
            @endif
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $monthAll ? 'all' : $month }}">
            <label for="pt-search" class="sr-only">{{ __('payroll_time.search') }}</label>
            <svg class="ziifra-time-attendance-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="search" id="pt-search" name="search" value="{{ $search }}" data-payroll-time-search
                placeholder="{{ __('daily_hours.search_employee') }}" class="ziifra-time-attendance-search-input" autocomplete="off">
        </form>

        <div class="ziifra-time-attendance-month-controls">
            <form method="GET" class="ziifra-time-attendance-month-form flex flex-wrap items-center gap-2" data-payroll-time-filter>
                @if (request('project_id') !== null && request('project_id') !== '')
                    <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                @endif
                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <label for="pt-year-select" class="sr-only">{{ __('payroll_time.year') }}</label>
                <select id="pt-year-select" name="year" class="ziifra-time-attendance-month-select" data-auto-submit aria-label="{{ __('payroll_time.year') }}">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
                <label for="pt-month-select" class="sr-only">{{ __('payroll_time.month') }}</label>
                <select id="pt-month-select" name="month" class="ziifra-time-attendance-month-select" data-auto-submit aria-label="{{ __('payroll_time.month') }}">
                    <option value="all" @selected($monthAll)>{{ __('payroll_time.all_months') }}</option>
                    @for ($m = 1; $m <= 12; $m++)
                        @php $opt = \Carbon\Carbon::create($year, $m, 1); @endphp
                        <option value="{{ $m }}" @selected(! $monthAll && $month === $m)>{{ $opt->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </form>

            @if (! $monthAll)
                <div class="ziifra-time-attendance-month-chips" role="group" aria-label="{{ __('payroll_time.month') }}">
                    @foreach ($quickMonths as $quickMonth)
                        @php
                            $chipYear = $quickMonth->year;
                            $chipMonth = $quickMonth->month;
                        @endphp
                        <a href="{{ route('payroll-time.index', $filterParams(['year' => $chipYear, 'month' => $chipMonth])) }}"
                            @class(['ziifra-time-attendance-month-chip', 'ziifra-time-attendance-month-chip--active' => $chipYear === $year && $chipMonth === $month])>
                            {{ $quickMonth->format('M') }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" class="flex items-center gap-2" data-payroll-time-filter>
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $monthAll ? 'all' : $month }}">
                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <label for="pt-project-toolbar" class="sr-only">{{ __('payroll_time.project') }}</label>
                <select id="pt-project-toolbar" name="project_id" class="ziifra-input !py-1.5 !text-xs" data-auto-submit>
                    <option value="" @selected(! request()->filled('project_id'))>{{ __('payroll_time.all_projects') }}</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected(request()->filled('project_id') && (int) request('project_id') === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if (empty($grid['rows']))
        <div class="ziifra-time-attendance-empty">
            <p class="font-medium">{{ __('payroll_time.empty') }}</p>
            <p class="mt-1 text-sm opacity-70">{{ __('payroll_time.empty_hint') }}</p>
        </div>
    @elseif ($monthAll)
        @include('app.payroll-time._grid-year')
    @else
        <div class="ziifra-time-attendance-grid-wrap" data-payroll-time-table>
            <table class="ziifra-time-attendance-grid">
                <thead>
                    <tr>
                        <th class="ziifra-time-attendance-grid-sticky">{{ __('payroll_time.employee') }}</th>
                        <th class="ziifra-time-attendance-grid-rate">{{ __('payroll_time.rate') }}</th>
                        @foreach ($grid['days'] as $day)
                            <th @class([
                                'ziifra-time-attendance-grid-day',
                                'ziifra-time-attendance-grid-day--today' => $isCurrentMonth && $day->isToday(),
                                'ziifra-time-attendance-grid-day--weekend' => $day->isWeekend(),
                            ])>{{ $day->format('j') }}</th>
                        @endforeach
                        <th class="ziifra-time-attendance-grid-total">{{ __('payroll_time.hours') }}</th>
                        <th class="ziifra-time-attendance-grid-pay">{{ __('payroll_time.trust') }}</th>
                        <th class="ziifra-time-attendance-grid-total">{{ __('payroll_time.total') }}</th>
                        <th class="ziifra-time-attendance-grid-status">{{ __('daily_hours.status') }}</th>
                        <th class="ziifra-time-attendance-grid-status">{{ __('payroll_time.download') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['rows'] as $row)
                        @php
                            $emp = $row['employee'];
                            $canEditRowHours = ($canManage || (($linkedEmployee ?? null)?->id === $emp->id))
                                && ($row['hours_editable'] ?? $grid['editable']);
                        @endphp
                        <tr data-pt-row data-employee-id="{{ $emp->id }}" data-rate="{{ $row['hourly_rate'] }}"
                            data-trust="{{ $row['trust_employee_percent'] }}" data-monthly="{{ $row['is_monthly'] ? '1' : '0' }}"
                            data-gross="{{ $row['gross'] }}" data-row-status="{{ $row['row_status'] ?? 'empty' }}">
                            <td class="ziifra-time-attendance-grid-sticky">
                                <div class="ziifra-time-attendance-employee">
                                    <span class="ziifra-time-attendance-avatar" aria-hidden="true">{{ $emp->initials() }}</span>
                                    <span class="min-w-0">
                                        <a href="{{ route('employees.show', $emp) }}" class="ziifra-time-attendance-employee-name" data-page-nav>{{ $emp->fullName() }}</a>
                                        <span class="ziifra-time-attendance-employee-code">{{ $emp->displayCode() }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="ziifra-time-attendance-grid-rate">
                                @if ($canManage && ! $row['is_monthly'])
                                    <span class="inline-flex items-center gap-0.5">
                                        <input type="number" min="0" step="0.01" value="{{ number_format($row['hourly_rate'], 2, '.', '') }}"
                                            data-pt-rate
                                            class="ziifra-time-attendance-cell ziifra-pt-rate w-14 text-right tabular-nums"
                                            aria-label="{{ __('payroll_time.rate') }}">
                                        <span class="text-[0.65rem] text-ziifra-muted">{{ $row['currency'] }}</span>
                                    </span>
                                @else
                                    <span class="tabular-nums">{{ $row['is_monthly'] ? __('payroll_time.fixed_monthly') : $currency.' '.number_format($row['hourly_rate'], 0) }}</span>
                                @endif
                            </td>
                            @foreach ($grid['days'] as $day)
                                @php
                                    $d = $day->format('Y-m-d');
                                    $h = $row['daily'][$d] ?? 0;
                                    $meta = $row['daily_meta'][$d] ?? ['status' => 'empty', 'approved_hours' => 0];
                                    $cellStatus = $meta['status'];
                                    $isToday = $day->isToday();
                                    $isWeekend = $day->isWeekend();
                                @endphp
                                <td @class([
                                    'ziifra-time-attendance-grid-cell',
                                    'ziifra-time-attendance-grid-cell--today' => $isToday,
                                    'ziifra-time-attendance-grid-cell--weekend' => $isWeekend,
                                ])>
                                    @if ($canEditRowHours)
                                        <input type="number" min="0" max="24" step="0.5"
                                            value="{{ $h > 0 ? rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.') : '' }}"
                                            data-pt-hours data-employee-id="{{ $emp->id }}" data-work-date="{{ $d }}"
                                            data-project-id="{{ $row['hours_project_id'] ?? $grid['project']?->id }}"
                                            data-approval-status="{{ $cellStatus }}"
                                            data-pt-approved-hours="{{ number_format((float) ($meta['approved_hours'] ?? 0), 2, '.', '') }}"
                                            @class([
                                                'ziifra-time-attendance-cell',
                                                'ziifra-time-attendance-cell--filled' => $h > 0 && $cellStatus === 'approved',
                                                'ziifra-time-attendance-cell--pending' => $h > 0 && $cellStatus === 'pending',
                                            ])
                                            aria-label="{{ $emp->fullName() }} — {{ $day->format('M j') }}">
                                    @else
                                        <span @class([
                                            'ziifra-time-attendance-cell-readonly',
                                            'ziifra-time-attendance-cell--filled' => $h > 0 && $cellStatus === 'approved',
                                            'ziifra-time-attendance-cell--pending' => $h > 0 && $cellStatus === 'pending',
                                        ])>{{ $h > 0 ? number_format($h, $h == floor($h) ? 0 : 1) : '' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="ziifra-time-attendance-grid-total tabular-nums" data-pt-total-hours title="{{ ($row['pending_hours'] ?? 0) > 0 ? __('payroll_time.pending_hours_hint', ['hours' => number_format($row['pending_hours'], 1)]) : '' }}">{{ number_format($row['total_hours'], 1) }}h</td>
                            <td class="ziifra-time-attendance-grid-pay">
                                @if ($canManage)
                                    <span class="inline-flex items-center gap-0.5">
                                        <input type="number" min="0" max="100" step="0.5"
                                            value="{{ rtrim(rtrim(number_format($row['trust_employee_percent'], 2, '.', ''), '0'), '.') }}"
                                            data-pt-trust
                                            class="ziifra-time-attendance-cell ziifra-pt-trust w-10 text-right tabular-nums"
                                            aria-label="{{ __('payroll_time.trust') }}">
                                        <span class="text-[0.65rem]">%</span>
                                    </span>
                                    <span class="mt-0.5 block text-[0.65rem] tabular-nums opacity-80" data-pt-trust-amount>{{ number_format($row['trust_employee'], 2) }}</span>
                                @else
                                    <span class="tabular-nums">{{ number_format($row['trust_employee_percent'], 1) }}%</span>
                                @endif
                            </td>
                            <td class="ziifra-time-attendance-grid-total font-semibold tabular-nums" data-pt-gross>{{ number_format($row['gross'], 2) }} {{ $currency }}</td>
                            <td class="ziifra-time-attendance-grid-status">
                                @if (($row['row_status'] ?? 'empty') === 'approved')
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--approved">{{ __('daily_hours.status_approved') }}</span>
                                @elseif (($row['row_status'] ?? 'empty') === 'pending')
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--pending">{{ __('daily_hours.status_pending') }}</span>
                                    @if ($canApprove ?? $canManage)
                                        <form method="POST" action="{{ route('payroll-time.hours.approve', ['employee' => $emp, 'organization' => $organization]) }}" class="mt-1">
                                            @csrf
                                            <input type="hidden" name="year" value="{{ $year }}">
                                            <input type="hidden" name="month" value="{{ $month }}">
                                            <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                                            @if ($search)
                                                <input type="hidden" name="search" value="{{ $search }}">
                                            @endif
                                            <button type="submit" class="text-[0.65rem] font-medium text-ziifra-accent-deep hover:underline">{{ __('daily_hours.approve_row') }}</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="ziifra-time-attendance-status ziifra-time-attendance-status--empty">—</span>
                                @endif
                            </td>
                            <td class="ziifra-time-attendance-grid-status whitespace-nowrap text-center text-xs">
                                <a href="{{ route('payroll-time.employee.export.pdf', ['employee' => $emp, 'year' => $year, 'month' => $month, 'project_id' => request('project_id'), 'search' => $search ?: null]) }}"
                                    class="text-ziifra-accent-deep hover:underline">PDF</a>
                                <span class="mx-0.5 opacity-40">·</span>
                                <a href="{{ route('payroll-time.employee.export.excel', ['employee' => $emp, 'year' => $year, 'month' => $month, 'project_id' => request('project_id'), 'search' => $search ?: null]) }}"
                                    class="text-ziifra-accent-deep hover:underline">Excel</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td class="ziifra-time-attendance-grid-sticky">{{ __('payroll_time.grand_total') }}</td>
                        <td></td>
                        <td colspan="{{ count($grid['days']) }}"></td>
                        <td class="ziifra-time-attendance-grid-total tabular-nums" data-pt-foot-hours>{{ number_format($grid['totals']['hours'], 1) }}h</td>
                        <td class="ziifra-time-attendance-grid-pay tabular-nums" data-pt-foot-trust>{{ number_format($grid['totals']['trust_employee'], 2) }}</td>
                        <td class="ziifra-time-attendance-grid-total tabular-nums" data-pt-foot-gross>{{ number_format($grid['totals']['gross'], 2) }} {{ $currency }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <footer class="ziifra-time-attendance-footer">
            <div class="ziifra-time-attendance-footer-stats">
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value">{{ count($grid['rows']) }}</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('daily_hours.footer_employees') }}</span>
                </div>
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value" data-pt-foot-hours>{{ number_format($grid['totals']['hours'], 1) }}h</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('payroll_time.approved_hours') }}</span>
                </div>
                @if (($grid['totals']['pending_hours'] ?? 0) > 0)
                    <div class="ziifra-time-attendance-footer-stat">
                        <span class="ziifra-time-attendance-footer-stat-value text-amber-600" data-pt-foot-pending>{{ number_format($grid['totals']['pending_hours'], 1) }}h</span>
                        <span class="ziifra-time-attendance-footer-stat-label">{{ __('payroll_time.pending_hours') }}</span>
                    </div>
                @endif
                <div class="ziifra-time-attendance-footer-stat">
                    <span class="ziifra-time-attendance-footer-stat-value" data-pt-foot-gross>{{ number_format($grid['totals']['gross'], 2) }} {{ $currency }}</span>
                    <span class="ziifra-time-attendance-footer-stat-label">{{ __('payroll_time.total') }}</span>
                </div>
            </div>
            @if (($canApprove ?? $canManage) && ($grid['totals']['pending_employees'] ?? 0) > 0)
                <div class="ziifra-time-attendance-footer-actions">
                    <form method="POST" action="{{ route('payroll-time.hours.approve-all', $organization) }}">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                        @if ($search)
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <button type="submit" class="ziifra-time-attendance-btn-ghost">{{ __('daily_hours.approve_all') }}</button>
                    </form>
                </div>
            @endif
        </footer>
    @endif
</section>
