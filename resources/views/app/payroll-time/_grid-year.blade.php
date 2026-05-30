@php
    $currency = $organization->currency ?? 'EUR';
@endphp

<div class="ziifra-time-attendance-grid-wrap" data-payroll-time-table>
    <table class="ziifra-time-attendance-grid">
        <thead>
            <tr>
                <th class="ziifra-time-attendance-grid-sticky">{{ __('payroll_time.employee') }}</th>
                <th class="ziifra-time-attendance-grid-rate">{{ __('payroll_time.rate') }}</th>
                @foreach ($grid['months'] as $monthDate)
                    <th class="ziifra-time-attendance-grid-day">{{ $monthDate->translatedFormat('M') }}</th>
                @endforeach
                <th class="ziifra-time-attendance-grid-total">{{ __('payroll_time.hours') }}</th>
                <th class="ziifra-time-attendance-grid-pay">{{ __('payroll_time.trust') }}</th>
                <th class="ziifra-time-attendance-grid-total">{{ __('payroll_time.total') }}</th>
                <th class="ziifra-time-attendance-grid-status">{{ __('payroll_time.download') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($grid['rows'] as $row)
                @php $emp = $row['employee']; @endphp
                <tr>
                    <td class="ziifra-time-attendance-grid-sticky">
                        <div class="ziifra-time-attendance-employee">
                            <span class="ziifra-time-attendance-avatar" aria-hidden="true">{{ $emp->initials() }}</span>
                            <span class="min-w-0">
                                <a href="{{ route('employees.show', $emp) }}" class="ziifra-time-attendance-employee-name" data-page-nav>{{ $emp->fullName() }}</a>
                                <span class="ziifra-time-attendance-employee-code">{{ $emp->displayCode() }}</span>
                            </span>
                        </div>
                    </td>
                    <td class="ziifra-time-attendance-grid-rate tabular-nums">
                        {{ $row['is_monthly'] ? __('payroll_time.fixed_monthly') : $currency.' '.number_format($row['hourly_rate'], 0) }}
                    </td>
                    @foreach (range(1, 12) as $m)
                        @php $h = $row['monthly_hours'][$m] ?? 0; @endphp
                        <td class="ziifra-time-attendance-grid-cell tabular-nums text-center text-xs">
                            {{ $h > 0 ? number_format($h, $h == floor($h) ? 0 : 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="ziifra-time-attendance-grid-total tabular-nums">{{ number_format($row['total_hours'], 1) }}h</td>
                    <td class="ziifra-time-attendance-grid-pay tabular-nums">{{ number_format($row['trust_employee'], 2) }}</td>
                    <td class="ziifra-time-attendance-grid-total font-semibold tabular-nums">{{ number_format($row['gross'], 2) }} {{ $currency }}</td>
                    <td class="ziifra-time-attendance-grid-status whitespace-nowrap text-center text-xs">
                        <a href="{{ route('payroll-time.employee.export.pdf', ['employee' => $emp, 'year' => $year, 'month' => 'all', 'project_id' => request('project_id')]) }}"
                            class="text-ziifra-accent-deep hover:underline">PDF</a>
                        <span class="mx-0.5 opacity-40">·</span>
                        <a href="{{ route('payroll-time.employee.export.excel', ['employee' => $emp, 'year' => $year, 'month' => 'all', 'project_id' => request('project_id')]) }}"
                            class="text-ziifra-accent-deep hover:underline">Excel</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-semibold">
                <td class="ziifra-time-attendance-grid-sticky">{{ __('payroll_time.grand_total') }}</td>
                <td></td>
                <td colspan="12"></td>
                <td class="ziifra-time-attendance-grid-total tabular-nums">{{ number_format($grid['totals']['hours'], 1) }}h</td>
                <td class="ziifra-time-attendance-grid-pay tabular-nums">{{ number_format($grid['totals']['trust_employee'], 2) }}</td>
                <td class="ziifra-time-attendance-grid-total tabular-nums">{{ number_format($grid['totals']['gross'], 2) }} {{ $currency }}</td>
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
            <span class="ziifra-time-attendance-footer-stat-value">{{ number_format($grid['totals']['hours'], 1) }}h</span>
            <span class="ziifra-time-attendance-footer-stat-label">{{ __('payroll_time.approved_hours') }}</span>
        </div>
        <div class="ziifra-time-attendance-footer-stat">
            <span class="ziifra-time-attendance-footer-stat-value">{{ number_format($grid['totals']['gross'], 2) }} {{ $currency }}</span>
            <span class="ziifra-time-attendance-footer-stat-label">{{ __('payroll_time.total') }}</span>
        </div>
    </div>
</footer>

<p class="mt-2 text-xs text-ziifra-muted">{{ __('payroll_time.year_view_hint') }}</p>
