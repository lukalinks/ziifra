@extends('layouts.app')

@section('title', __('reports.title'))
@section('header', __('reports.title'))

@section('content')
@php
    $isTeam = ($report['scope'] ?? 'company') === 'team';
    $tz = $organization->timezone ?? config('app.timezone');
    $generatedLocal = $report['generatedAt']->timezone($tz);
    $deptRows = $report['workforce']['departments'];
    $deptMax = $deptRows === [] ? 1 : max(array_column($deptRows, 'count'));
    $trendApproved = $report['leave']['trend']['approved'];
    $trendPending = $report['leave']['trend']['pending'];
    $maxApproved = max(1, ...array_map(fn ($v) => (float) $v, $trendApproved));
    $maxPending = max(1, ...array_map(fn ($v) => (int) $v, $trendPending));
    $currency = $organization->currency ?? 'EUR';
@endphp

<div class="relative mb-8 overflow-hidden rounded-2xl border border-ziifra-line/80 bg-gradient-to-br from-ziifra-paper via-ziifra-paper to-ziifra-cream/50 p-6 shadow-sm sm:p-8">
    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-ziifra-accent/10 blur-3xl"></div>
    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-2xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border border-ziifra-line/80 bg-ziifra-paper/80 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                    {{ $isTeam ? __('reports.scope_team') : __('reports.scope_company') }}
                </span>
                @if ($report['hasPayroll'] && ! $isTeam)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-900">{{ __('reports.badge_payroll') }}</span>
                @endif
                @if ($report['canViewFinance'])
                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-900">{{ __('reports.badge_finance') }}</span>
                @endif
            </div>
            <p class="mt-3 text-base text-ziifra-ink leading-relaxed">
                {{ $isTeam ? __('reports.team_subtitle') : __('reports.subtitle') }}
            </p>
            <p class="mt-2 flex flex-wrap items-center gap-x-2 text-xs text-ziifra-muted">
                <svg class="inline h-3.5 w-3.5 shrink-0 text-ziifra-accent-deep" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('reports.generated', ['time' => $generatedLocal->format('M j, Y · H:i')]) }}
                <span class="text-ziifra-line">·</span>
                <span>{{ $generatedLocal->timezoneName }}</span>
            </p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
            <a href="{{ route('reports.export', ['format' => 'csv']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-ziifra-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ziifra-accent-deep">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('reports.export_csv') }}
            </a>
            <a href="{{ route('reports.export', ['format' => 'json']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-ziifra-line bg-ziifra-paper px-4 py-2.5 text-sm font-semibold text-ziifra-ink shadow-sm transition hover:bg-ziifra-cream">
                <svg class="h-4 w-4 text-ziifra-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22 12l-4.75 5.25m-9.5-10.5L2.25 12l4.75 5.25"/></svg>
                {{ __('reports.export_json') }}
            </a>
        </div>
    </div>
    <p class="relative mt-4 text-xs text-ziifra-muted">{{ __('reports.export_hint') }}</p>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.active_employees') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $report['workforce']['active_employees'] }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.pending_leave') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $report['leave']['pending'] }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.approved_days_month') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ number_format($report['leave']['approved_days_month'], 1) }}</p>
    </div>
    <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.hours_logged_month') }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ziifra-ink">{{ $report['work']['hours_logged_month'] }}<span class="text-lg font-medium text-ziifra-muted">h</span></p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Workforce --}}
    <section class="overflow-hidden rounded-2xl border border-ziifra-line/80 bg-ziifra-paper shadow-sm">
        <div class="border-b border-ziifra-line/60 bg-gradient-to-r from-ziifra-cream/50 to-ziifra-paper px-5 py-4">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('reports.workforce') }}</h2>
            <p class="mt-0.5 text-xs text-ziifra-muted">{{ __('reports.workforce_hint') }}</p>
        </div>
        <div class="space-y-5 p-5">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-ziifra-line/50 bg-ziifra-paper/80 px-3 py-3">
                    <p class="text-xs text-ziifra-muted">{{ __('reports.new_hires_month') }}</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-ziifra-ink">{{ $report['workforce']['new_hires_month'] }}</p>
                </div>
                <div class="rounded-lg border border-ziifra-line/50 bg-ziifra-paper/80 px-3 py-3">
                    <p class="text-xs text-ziifra-muted">{{ __('reports.by_department') }}</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-ziifra-ink">{{ count($deptRows) }}</p>
                </div>
            </div>
            @if (count($deptRows) > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('reports.by_department') }}</h3>
                    <ul class="mt-3 space-y-3">
                        @foreach ($deptRows as $row)
                            @php $pct = round(($row['count'] / $deptMax) * 100); @endphp
                            <li>
                                <div class="flex justify-between gap-3 text-sm">
                                    <span class="truncate font-medium text-ziifra-ink">{{ $row['label'] }}</span>
                                    <span class="shrink-0 tabular-nums font-semibold text-ziifra-ink">{{ $row['count'] }}</span>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ziifra-cream">
                                    <div class="h-full rounded-full bg-gradient-to-r from-ziifra-accent to-ziifra-accent-deep transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="rounded-lg border border-dashed border-ziifra-line/80 bg-ziifra-cream/30 px-4 py-6 text-center text-sm text-ziifra-muted">{{ __('reports.no_departments') }}</p>
            @endif
        </div>
    </section>

    {{-- Leave --}}
    <section class="overflow-hidden rounded-2xl border border-ziifra-line/80 bg-ziifra-paper shadow-sm">
        <div class="border-b border-ziifra-line/60 bg-gradient-to-r from-ziifra-cream/50 to-ziifra-paper px-5 py-4">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('reports.leave') }}</h2>
            <p class="mt-0.5 text-xs text-ziifra-muted">{{ __('reports.leave_hint') }}</p>
        </div>
        <div class="space-y-5 p-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('reports.leave_trend') }}</h3>
                <div class="mt-4 space-y-4">
                    @foreach ($report['leave']['trend']['labels'] as $i => $label)
                        @php
                            $ap = (float) ($trendApproved[$i] ?? 0);
                            $pe = (int) ($trendPending[$i] ?? 0);
                            $apPct = round(($ap / $maxApproved) * 100);
                            $pePct = round(($pe / $maxPending) * 100);
                        @endphp
                        <div class="rounded-lg border border-ziifra-line/40 bg-ziifra-paper/40 px-3 py-3">
                            <div class="flex items-center justify-between gap-2 text-xs font-medium text-ziifra-ink">
                                <span>{{ $label }}</span>
                                <span class="tabular-nums text-ziifra-muted">{{ number_format($ap, 1) }} {{ __('reports.approved_days_short') }} · {{ $pe }} {{ __('reports.pending_requests_short') }}</span>
                            </div>
                            <div class="mt-2 space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-24 shrink-0 text-[10px] font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.approved_days') }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-ziifra-cream">
                                        <div class="h-full rounded-full bg-ziifra-accent" style="width: {{ $apPct }}%"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-24 shrink-0 text-[10px] font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.pending_requests') }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-ziifra-cream">
                                        <div class="h-full rounded-full bg-ziifra-muted/60" style="width: {{ $pePct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-ziifra-line/50">
                <table class="min-w-full divide-y divide-ziifra-line/60 text-xs">
                    <thead class="bg-ziifra-cream/40 text-left text-ziifra-muted">
                        <tr>
                            <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('reports.table_month') }}</th>
                            <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('reports.approved_days') }}</th>
                            <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('reports.pending_requests') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ziifra-line/40 bg-ziifra-paper">
                        @foreach ($report['leave']['trend']['labels'] as $i => $label)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 font-medium text-ziifra-ink">{{ $label }}</td>
                                <td class="whitespace-nowrap px-3 py-2 tabular-nums text-ziifra-ink">{{ number_format($report['leave']['trend']['approved'][$i] ?? 0, 1) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 tabular-nums text-ziifra-ink">{{ $report['leave']['trend']['pending'][$i] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($report['finance'])
        <section class="overflow-hidden rounded-2xl border border-sky-200/80 bg-gradient-to-br from-ziifra-paper to-sky-50/40 shadow-sm lg:col-span-1">
            <div class="border-b border-sky-100 bg-ziifra-paper/70 px-5 py-4">
                <h2 class="text-sm font-semibold text-sky-950">{{ __('reports.finance') }}</h2>
                <p class="mt-0.5 text-xs text-sky-800/80">{{ __('reports.finance_hint') }}</p>
            </div>
            <dl class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm">
                    <dt class="text-xs font-medium text-sky-900/70">{{ __('reports.invoices_unpaid') }}</dt>
                    <dd class="mt-2 text-lg font-semibold tabular-nums text-sky-950">{{ $report['finance']['invoices_unpaid_count'] }}</dd>
                    @if ($report['finance']['invoices_unpaid_total'] > 0)
                        <p class="mt-1 text-xs tabular-nums text-sky-800">{{ number_format($report['finance']['invoices_unpaid_total'], 2) }} {{ $currency }}</p>
                    @endif
                </div>
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm">
                    <dt class="text-xs font-medium text-sky-900/70">{{ __('reports.expenses_pending') }}</dt>
                    <dd class="mt-2 text-lg font-semibold tabular-nums text-sky-950">{{ $report['finance']['expenses_pending_count'] }}</dd>
                </div>
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm">
                    <dt class="text-xs font-medium text-sky-900/70">{{ __('reports.expenses_approved_month') }}</dt>
                    <dd class="mt-2 text-lg font-semibold tabular-nums text-sky-950">{{ number_format($report['finance']['expenses_approved_month'], 2) }} {{ $currency }}</dd>
                </div>
            </dl>
        </section>
    @endif

    @if ($report['payroll'])
        <section class="overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-ziifra-paper to-emerald-50/40 shadow-sm lg:col-span-1">
            <div class="border-b border-emerald-100 bg-ziifra-paper/70 px-5 py-4">
                <h2 class="text-sm font-semibold text-emerald-950">{{ __('reports.payroll') }}</h2>
                <p class="mt-0.5 text-xs text-emerald-900/75">{{ __('reports.payroll_hint') }}</p>
            </div>
            <dl class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm sm:col-span-1">
                    <dt class="text-xs font-medium text-emerald-900/70">{{ __('reports.latest_payroll') }}</dt>
                    <dd class="mt-2 text-lg font-semibold text-emerald-950">
                        {{ $report['payroll']['latest_run'] ?? '—' }}
                        @if (! empty($report['payroll']['latest_status']))
                            <span class="block text-xs font-normal text-emerald-800/80">{{ $report['payroll']['latest_status'] }}</span>
                        @endif
                    </dd>
                </div>
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm">
                    <dt class="text-xs font-medium text-emerald-900/70">{{ __('reports.locked_runs') }}</dt>
                    <dd class="mt-2 text-lg font-semibold tabular-nums text-emerald-950">{{ $report['payroll']['locked_runs'] }}</dd>
                </div>
                <div class="rounded-xl border border-white/80 bg-ziifra-paper/90 px-4 py-3 shadow-sm">
                    <dt class="text-xs font-medium text-emerald-900/70">{{ __('reports.gross_last_month') }}</dt>
                    <dd class="mt-2 text-lg font-semibold tabular-nums text-emerald-950">{{ number_format($report['payroll']['gross_last_month'], 2) }} {{ $currency }}</dd>
                </div>
            </dl>
        </section>
    @endif

    {{-- Work spans full width when finance+payroll fill row oddly — use full width always for clarity --}}
    <section class="overflow-hidden rounded-2xl border border-ziifra-line/80 bg-ziifra-paper shadow-sm lg:col-span-2">
        <div class="border-b border-ziifra-line/60 bg-gradient-to-r from-ziifra-cream/50 to-ziifra-paper px-5 py-4">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('reports.work') }}</h2>
            <p class="mt-0.5 text-xs text-ziifra-muted">{{ __('reports.work_hint') }}</p>
        </div>
        <div class="p-5">
            <div class="grid gap-6 lg:grid-cols-12 lg:items-center">
                <div class="flex justify-center lg:col-span-3">
                    @php $pct = min(100, max(0, (int) $report['work']['task_completion_percent'])); @endphp
                    <div class="relative h-36 w-36 shrink-0">
                        <svg class="absolute inset-0 h-full w-full -rotate-90 drop-shadow-sm" viewBox="0 0 36 36" aria-hidden="true">
                            <path stroke="#ede8dc" stroke-width="3" fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none" class="text-ziifra-accent-deep"
                                stroke-dasharray="{{ $pct }}, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <p class="text-2xl font-bold tabular-nums text-ziifra-ink">{{ $pct }}%</p>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-ziifra-muted">{{ __('reports.task_completion') }}</p>
                        </div>
                    </div>
                </div>
                <dl class="grid gap-4 sm:grid-cols-3 lg:col-span-9 lg:grid-cols-3">
                    <div class="rounded-xl border border-ziifra-line/50 bg-ziifra-paper/60 px-4 py-4">
                        <dt class="text-xs font-medium text-ziifra-muted">{{ __('reports.active_projects') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold tabular-nums text-ziifra-ink">{{ $report['work']['active_projects'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-ziifra-line/50 bg-ziifra-paper/60 px-4 py-4">
                        <dt class="text-xs font-medium text-ziifra-muted">{{ __('reports.open_time_entries') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold tabular-nums text-ziifra-ink">{{ $report['work']['open_time_entries'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-ziifra-line/50 bg-ziifra-paper/60 px-4 py-4">
                        <dt class="text-xs font-medium text-ziifra-muted">{{ __('reports.hours_logged_month') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold tabular-nums text-ziifra-ink">{{ $report['work']['hours_logged_month'] }}<span class="text-base font-medium text-ziifra-muted">h</span></dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</div>
@endsection
