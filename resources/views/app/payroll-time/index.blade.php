@extends('layouts.app')

@section('title', __('payroll_time.title'))
@section('header', __('payroll_time.title'))

@section('content')
@php
    $dayCount = count($grid['days']);
    $exportMonthParams = ['year' => $year, 'month' => $month, 'project_id' => request('project_id')];
    $exportYearParams = ['year' => $year, 'project_id' => request('project_id')];
@endphp

<div class="ziifra-dashboard-page" data-payroll-time
    data-upsert-url="{{ route('payroll-time.hours.upsert') }}"
    data-rate-url-template="{{ route('payroll-time.rate.update', ['employee' => '__EMPLOYEE__']) }}"
    data-project-id="{{ $grid['project']?->id }}"
    data-csrf="{{ csrf_token() }}">

    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-ziifra-muted">{{ __('payroll_time.subtitle') }}</p>
            @unless ($grid['editable'])
                <p class="mt-1 text-xs text-amber-700">{{ __('payroll_time.select_project_to_edit') }}</p>
            @endunless
        </div>
        <a href="{{ route('settings.payroll.edit') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-ziifra-line/80 text-ziifra-muted hover:text-ziifra-ink" title="{{ __('payroll_time.settings') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3" data-payroll-time-filter>
        <div>
            <label class="ziifra-label-field" for="pt-year">{{ __('payroll_time.year') }}</label>
            <select id="pt-year" name="year" class="ziifra-input !py-2 !text-sm" data-auto-submit>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ziifra-label-field" for="pt-month">{{ __('payroll_time.month') }}</label>
            <select id="pt-month" name="month" class="ziifra-input !py-2 !text-sm" data-auto-submit>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="ziifra-label-field" for="pt-project">{{ __('payroll_time.project') }}</label>
            <select id="pt-project" name="project_id" class="ziifra-input !py-2 !text-sm" data-auto-submit>
                <option value="">{{ __('payroll_time.all_projects') }}</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[12rem]">
            <label class="ziifra-label-field" for="pt-search">{{ __('payroll_time.search') }}</label>
            <input id="pt-search" type="search" name="search" value="{{ $search }}" placeholder="{{ __('payroll_time.search') }}"
                autocomplete="off" data-payroll-time-search class="ziifra-input !w-full !py-2 !text-sm">
        </div>
    </form>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('payroll_time.download_all') }}:</span>
        <a href="{{ route('payroll-time.export.pdf', $exportMonthParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.pdf_month') }}</a>
        <a href="{{ route('payroll-time.export.excel', $exportMonthParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.excel_month') }}</a>
        <span class="mx-1 text-ziifra-line">|</span>
        <a href="{{ route('payroll-time.export.pdf', $exportYearParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.pdf_year') }}</a>
        <a href="{{ route('payroll-time.export.excel', $exportYearParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.excel_year') }}</a>
    </div>

    @if (empty($grid['rows']))
        <div class="ziifra-dashboard-empty py-12">
            <p class="font-medium text-ziifra-ink">{{ __('payroll_time.empty') }}</p>
            <p class="mt-1 text-sm text-ziifra-muted">{{ __('payroll_time.empty_hint') }}</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ziifra-line/80">
            <table class="ziifra-table min-w-full text-xs">
                <thead class="bg-ziifra-cream/60 text-ziifra-muted">
                    <tr>
                        <th class="sticky left-0 z-10 bg-ziifra-cream/60 px-3 py-2 text-left">{{ __('payroll_time.employee') }}</th>
                        @foreach ($grid['days'] as $day)
                            <th @class(['px-1 py-2 text-center', 'text-ziifra-accent-deep' => $day->isWeekend()])>{{ $day->format('j') }}</th>
                        @endforeach
                        <th class="px-2 py-2 text-right">{{ __('payroll_time.hours') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('payroll_time.rate') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('payroll_time.trust') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('payroll_time.total') }}</th>
                        <th class="px-2 py-2 text-center">{{ __('payroll_time.download') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ziifra-line/50">
                    @foreach ($grid['rows'] as $row)
                        @php $emp = $row['employee']; @endphp
                        <tr data-pt-row data-employee-id="{{ $emp->id }}" data-rate="{{ $row['hourly_rate'] }}"
                            data-trust="{{ $row['trust_employee_percent'] }}" data-monthly="{{ $row['is_monthly'] ? '1' : '0' }}"
                            data-gross="{{ $row['gross'] }}">
                            <td class="sticky left-0 z-10 whitespace-nowrap bg-ziifra-paper px-3 py-2 font-medium text-ziifra-ink">
                                {{ $emp->fullName() }}
                                <span class="block text-[0.65rem] font-normal text-ziifra-muted">{{ $emp->displayCode() }}</span>
                            </td>
                            @foreach ($grid['days'] as $day)
                                @php $d = $day->format('Y-m-d'); $h = $row['daily'][$d]; @endphp
                                <td class="px-0.5 py-1 text-center">
                                    @if ($grid['editable'] && $canManage)
                                        <input type="number" min="0" max="24" step="0.5"
                                            value="{{ $h > 0 ? rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.') : '' }}"
                                            data-pt-hours data-employee-id="{{ $emp->id }}" data-work-date="{{ $d }}"
                                            class="ziifra-pt-cell h-7 w-9 rounded border border-ziifra-line/70 bg-ziifra-paper px-0.5 text-center text-[0.7rem] tabular-nums focus:border-ziifra-accent focus:outline-none">
                                    @else
                                        <span class="tabular-nums text-ziifra-muted">{{ $h > 0 ? number_format($h, 1) : '—' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-2 py-2 text-right font-medium tabular-nums" data-pt-total-hours>{{ number_format($row['total_hours'], 1) }}</td>
                            <td class="px-2 py-2 text-right">
                                @if ($canManage && ! $row['is_monthly'])
                                    <span class="inline-flex items-center gap-1">
                                        <input type="number" min="0" step="0.01" value="{{ number_format($row['hourly_rate'], 2, '.', '') }}"
                                            data-pt-rate
                                            class="ziifra-pt-rate h-7 w-16 rounded border border-ziifra-line/70 bg-ziifra-paper px-1 text-right text-[0.7rem] tabular-nums focus:border-ziifra-accent focus:outline-none">
                                        <span class="text-[0.6rem] text-ziifra-muted">{{ $row['currency'] }}</span>
                                    </span>
                                @else
                                    <span class="tabular-nums text-ziifra-muted">{{ $row['is_monthly'] ? __('payroll_time.fixed_monthly') : number_format($row['hourly_rate'], 2).' '.$row['currency'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right">
                                @if ($canManage)
                                    <span class="inline-flex items-center gap-1">
                                        <input type="number" min="0" max="100" step="0.5" value="{{ rtrim(rtrim(number_format($row['trust_employee_percent'], 2, '.', ''), '0'), '.') }}"
                                            data-pt-trust
                                            class="ziifra-pt-trust h-7 w-12 rounded border border-ziifra-line/70 bg-ziifra-paper px-1 text-right text-[0.7rem] tabular-nums focus:border-ziifra-accent focus:outline-none">
                                        <span class="text-[0.6rem] text-ziifra-muted">%</span>
                                    </span>
                                    <span class="mt-0.5 block text-[0.6rem] tabular-nums text-ziifra-muted" data-pt-trust-amount>{{ number_format($row['trust_employee'], 2) }}</span>
                                @else
                                    <span class="tabular-nums text-ziifra-muted">{{ number_format($row['trust_employee'], 2) }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right font-semibold tabular-nums" data-pt-gross>{{ number_format($row['gross'], 2) }}</td>
                            <td class="whitespace-nowrap px-2 py-2 text-center">
                                <a href="{{ route('payroll-time.employee.export.pdf', ['employee' => $emp, 'year' => $year, 'month' => $month, 'project_id' => request('project_id')]) }}"
                                    class="text-ziifra-accent-deep hover:underline" title="{{ __('payroll_time.download_pdf') }}">PDF</a>
                                <span class="mx-1 text-ziifra-line">·</span>
                                <a href="{{ route('payroll-time.employee.export.excel', ['employee' => $emp, 'year' => $year, 'month' => $month, 'project_id' => request('project_id')]) }}"
                                    class="text-ziifra-accent-deep hover:underline" title="{{ __('payroll_time.download_excel') }}">Excel</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-ziifra-cream/40 font-semibold">
                    <tr>
                        <td class="sticky left-0 z-10 bg-ziifra-cream/40 px-3 py-2">{{ __('payroll_time.grand_total') }}</td>
                        <td colspan="{{ $dayCount }}"></td>
                        <td class="px-2 py-2 text-right tabular-nums" data-pt-foot-hours>{{ number_format($grid['totals']['hours'], 1) }}</td>
                        <td></td>
                        <td class="px-2 py-2 text-right tabular-nums" data-pt-foot-trust>{{ number_format($grid['totals']['trust_employee'], 2) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums" data-pt-foot-gross>{{ number_format($grid['totals']['gross'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-3 text-xs text-ziifra-muted">{{ __('payroll_time.hours_edit_hint') }}</p>
    @endif
</div>

@push('scripts')
    @vite('resources/js/payroll-time-grid.js')
@endpush
@endsection
