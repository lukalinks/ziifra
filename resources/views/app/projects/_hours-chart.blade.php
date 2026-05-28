@php
    $maxDay = $hoursChart['max_day_hours'] ?? 0;
    $hasData = $hoursChart['by_date_detail']->isNotEmpty();
@endphp
<div class="ziifra-hours-chart-card">
    <div class="ziifra-hours-chart-head">
        <div class="ziifra-hours-chart-head-main">
            <h3 class="ziifra-hours-chart-title">{{ __('projects.hours_chart') }}</h3>
            <span class="ziifra-hours-chart-total">{{ number_format($hoursChart['total_hours'], 1) }} h</span>
            <span class="ziifra-hours-chart-period">
                {{ $chartMonth ? \Carbon\Carbon::create($chartYear, $chartMonth)->translatedFormat('M Y') : $chartYear }}
            </span>
        </div>
        <form method="GET" class="ziifra-hours-chart-filters">
            <input type="hidden" name="tab" value="hours">
            <input type="hidden" name="month" value="{{ $selectedMonth ?? now()->format('Y-m') }}">
            <select name="chart_year" class="ziifra-hours-chart-select" onchange="this.form.submit()">
                @for ($y = now()->year - 2; $y <= now()->year + 5; $y++)
                    <option value="{{ $y }}" @selected($chartYear === $y)>{{ $y }}</option>
                @endfor
            </select>
            <select name="chart_month" class="ziifra-hours-chart-select" onchange="this.form.submit()">
                <option value="">{{ __('payroll_time.all_months') }}</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($chartMonth === $m)>{{ \Carbon\Carbon::create(null, $m)->format('M') }}</option>
                @endfor
            </select>
        </form>
    </div>

    @if ($hasData)
        <div class="ziifra-hours-chart" role="img" aria-label="{{ __('projects.hours_chart') }}">
            @foreach ($hoursChart['by_date_detail'] as $day)
                @php
                    $dateLabel = \Carbon\Carbon::parse($day['date']);
                    $pct = $maxDay > 0 ? max(4, round(($day['hours'] / $maxDay) * 100)) : 0;
                    $names = $day['people']->pluck('name')->implode(', ');
                @endphp
                <div class="ziifra-hours-chart-col" title="{{ $dateLabel->translatedFormat('D, M j') }} — {{ number_format($day['hours'], 1) }}h · {{ $names }}">
                    <span class="ziifra-hours-chart-value">{{ rtrim(rtrim(number_format($day['hours'], 1), '0'), '.') }}</span>
                    <span class="ziifra-hours-chart-bar" style="height: {{ $pct }}%"></span>
                    <span class="ziifra-hours-chart-label">{{ $dateLabel->format('j') }}</span>
                </div>
            @endforeach
        </div>

        <div class="ziifra-hours-chart-breakdown">
            <div class="ziifra-hours-chart-breakdown-block">
                <span class="ziifra-hours-chart-breakdown-label">{{ __('projects.hours_by_employee') }}</span>
                <div class="ziifra-hours-chart-breakdown-chips">
                    @foreach ($hoursChart['by_employee'] as $row)
                        <span class="ziifra-hours-chart-chip">{{ $row['name'] }} · {{ number_format($row['hours'], 1) }}h</span>
                    @endforeach
                </div>
            </div>
            <div class="ziifra-hours-chart-breakdown-block ziifra-hours-chart-breakdown-block--dates">
                <span class="ziifra-hours-chart-breakdown-label">{{ __('projects.hours_by_date') }}</span>
                <ul class="ziifra-hours-chart-date-list">
                    @foreach ($hoursChart['by_date_detail']->reverse() as $day)
                        <li class="ziifra-hours-chart-date-row">
                            <span class="text-[#cdd1db]">{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('D, M j') }}</span>
                            <span class="shrink-0 tabular-nums text-[#e8eaef]">
                                {{ collect($day['people'])->map(fn ($p) => $p['name'].' '.number_format($p['hours'], 1).'h')->implode(', ') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @else
        <div class="ziifra-hours-chart-empty">
            <p class="text-xs text-[#8b91a3]">{{ __('projects.hours_chart_empty') }}</p>
        </div>
    @endif
</div>
