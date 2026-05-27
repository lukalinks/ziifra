@extends('layouts.app')

@section('title', $run->periodLabel())
@section('header', $run->periodLabel())

@section('content')
@php
    $currency = $organization->currency ?? 'EUR';
    $money = fn (float $amount) => $currency.' '.number_format($amount, 2);
    $taxCases = \App\Enums\AllowanceTaxTreatment::cases();
    $kindCases = \App\Enums\PayrollAllowanceKind::cases();
    $employeeCount = $run->items->count();
    $monthShort = \Carbon\Carbon::create($run->year, $run->month, 1)->format('M');
@endphp

<div class="ziifra-dashboard-page ziifra-payroll-detail">
    <a href="{{ route('payroll.index') }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('payroll.back_to_list') }}
    </a>

    <section class="ziifra-payroll-detail-hero">
        <div class="relative z-[1] grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
            <div class="min-w-0">
                <div class="ziifra-payroll-detail-hero-main">
                    <span class="ziifra-payroll-detail-icon" aria-hidden="true">{{ $monthShort }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span @class([
                                'ziifra-list-badge',
                                'ziifra-list-badge-success' => $run->isLocked(),
                                'ziifra-list-badge-warning' => ! $run->isLocked(),
                            ])>{{ $run->status->label() }}</span>
                            <span class="ziifra-employee-profile-chip">{{ $run->periodSlug() }}</span>
                        </div>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ziifra-ink sm:text-3xl">{{ $run->periodLabel() }}</h1>
                        <p class="mt-1 text-sm text-ziifra-muted">{{ __('payroll.period') }}</p>
                        @if ($run->isLocked() && $run->locked_at)
                            <p class="mt-3 text-xs text-ziifra-muted">
                                Locked {{ $run->locked_at->format('d M Y H:i') }}
                                @if ($run->lockedBy)
                                    · {{ $run->lockedBy->name }}
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @if ($run->items->isNotEmpty())
                    <div class="ziifra-payroll-detail-actions mt-5">
                        <a href="{{ route('payroll.export-pdfs', $run) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll.download_all_pdf') }}</a>
                        <a href="{{ route('payroll.export-csv', $run) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll.download_csv') }}</a>
                        <form method="POST" action="{{ route('payroll.email-payslips', $run) }}"
                            data-confirm="{{ __('payroll.email_confirm_all') }}"
                            data-confirm-accept="{{ __('payroll.email_all_payslips') }}">
                            @csrf
                            <button type="submit" class="ziifra-btn-app-outline !text-sm">{{ __('payroll.email_all_payslips') }}</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="ziifra-payroll-detail-summary-card">
                <span class="ziifra-payroll-detail-summary-label">{{ __('payroll.columns.net') }}</span>
                <span class="ziifra-payroll-detail-summary-value">{{ $money($totals['net']) }}</span>
                <span class="ziifra-payroll-detail-summary-hint">{{ trans_choice('payroll.employee_count', $employeeCount, ['count' => $employeeCount]) }}</span>
            </div>
        </div>
    </section>

    <div class="ziifra-payroll-detail-stats">
        <div class="ziifra-payroll-detail-stat">
            <span class="ziifra-payroll-detail-stat-label">{{ __('payroll.employees') }}</span>
            <span class="ziifra-payroll-detail-stat-value">{{ $employeeCount }}</span>
        </div>
        <div class="ziifra-payroll-detail-stat">
            <span class="ziifra-payroll-detail-stat-label">{{ __('payroll.columns.taxable_gross') }}</span>
            <span class="ziifra-payroll-detail-stat-value">{{ $money($totals['gross']) }}</span>
        </div>
        <div class="ziifra-payroll-detail-stat">
            <span class="ziifra-payroll-detail-stat-label">{{ __('payroll.columns.tax') }}</span>
            <span class="ziifra-payroll-detail-stat-value">{{ $money($totals['income_tax']) }}</span>
        </div>
        <div class="ziifra-payroll-detail-stat">
            <span class="ziifra-payroll-detail-stat-label">{{ __('payroll.columns.net') }}</span>
            <span class="ziifra-payroll-detail-stat-value">{{ $money($totals['net']) }}</span>
        </div>
    </div>

    <p class="ziifra-payroll-rules-notice mb-6">{{ __('payroll.rules_notice') }}</p>

    @if ($run->isDraft())
        <form method="POST" action="{{ route('payroll.update', $run) }}">
            @csrf
            @method('PUT')
    @endif

    <section class="ziifra-payroll-detail-workspace">
        <div class="ziifra-payroll-detail-workspace-head">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('payroll.employees') }}</h2>
            @if ($run->isDraft())
                <span class="text-xs text-ziifra-muted">{{ __('payroll.save_draft') }}</span>
            @endif
        </div>

        @if ($run->items->isEmpty())
            <div class="ziifra-dashboard-empty py-12">
                <p class="text-sm text-ziifra-muted">{{ __('payroll.empty') }}</p>
            </div>
        @else
            <div class="ziifra-table-scroll">
                <table class="ziifra-payroll-detail-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="ziifra-payroll-detail-sticky">{{ __('payroll.columns.employee') }}</th>
                            <th class="text-right">{{ __('payroll.columns.base_gross') }}</th>
                            <th class="min-w-[14rem]">{{ __('payroll.columns.allowance_lines') }}</th>
                            <th class="text-right">{{ __('payroll.columns.taxable_allowances') }}</th>
                            <th class="text-right">{{ __('payroll.columns.exempt_allowances') }}</th>
                            <th class="text-right">{{ __('payroll.columns.taxable_gross') }}</th>
                            <th class="text-right">{{ __('payroll.columns.employee_pension') }}</th>
                            <th class="text-right">{{ __('payroll.columns.employer_pension') }}</th>
                            <th class="text-right">{{ __('payroll.columns.tax') }}</th>
                            <th class="text-right">{{ __('payroll.columns.net') }}</th>
                            <th class="text-right">{{ __('payroll.columns.payslip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($run->items as $item)
                            @php
                                $oldLines = old('items.'.$item->id.'.allowance_lines');
                                if (is_array($oldLines)) {
                                    $draftLines = collect(array_values($oldLines))->map(fn ($r) => is_array($r) ? $r : [])->values();
                                } else {
                                    $draftLines = $item->allowanceLines->map(fn ($l) => [
                                        'label' => $l->label,
                                        'amount' => $l->amount,
                                        'tax_treatment' => $l->tax_treatment->value,
                                        'kind' => $l->kind->value,
                                    ])->values();
                                }
                                if ($run->isDraft()) {
                                    $draftLines->push([
                                        'label' => '',
                                        'amount' => '',
                                        'tax_treatment' => 'taxable',
                                        'kind' => 'recurring',
                                    ]);
                                }
                            @endphp
                            <tr>
                                <td class="ziifra-payroll-detail-sticky font-medium text-ziifra-ink">{{ $item->employeeName() }}</td>
                                <td class="text-right">
                                    @if ($run->isDraft())
                                        <input type="number" step="0.01" min="0"
                                            name="items[{{ $item->id }}][base_gross_salary]"
                                            value="{{ old('items.'.$item->id.'.base_gross_salary', $item->base_gross_salary) }}"
                                            class="ziifra-payroll-detail-input">
                                    @else
                                        {{ $money((float) $item->base_gross_salary) }}
                                    @endif
                                </td>
                                <td>
                                    @if ($run->isDraft())
                                        <p class="mb-2 text-xs text-ziifra-muted">{{ __('payroll.allowance_lines_editor_help') }}</p>
                                        <div class="payroll-allowance-lines space-y-2"
                                            data-payroll-allowance-lines
                                            data-item-id="{{ $item->id }}"
                                            data-next-index="{{ $draftLines->count() }}">
                                            <div class="payroll-allowance-line-rows space-y-2">
                                                @foreach ($draftLines as $idx => $line)
                                                    <div class="payroll-allowance-line rounded-lg border border-ziifra-line/70 bg-ziifra-cream/20 p-2">
                                                        <div class="flex flex-wrap gap-1">
                                                            <input type="text"
                                                                name="items[{{ $item->id }}][allowance_lines][{{ $idx }}][label]"
                                                                value="{{ $line['label'] ?? '' }}"
                                                                placeholder="{{ __('payroll.allowance_label_placeholder') }}"
                                                                class="min-w-[6rem] flex-1 rounded border border-ziifra-line px-2 py-1 text-xs">
                                                            <input type="number" step="0.01" min="0"
                                                                name="items[{{ $item->id }}][allowance_lines][{{ $idx }}][amount]"
                                                                value="{{ $line['amount'] ?? '' }}"
                                                                class="w-24 rounded border border-ziifra-line px-2 py-1 text-right text-xs">
                                                        </div>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            <select name="items[{{ $item->id }}][allowance_lines][{{ $idx }}][tax_treatment]"
                                                                class="rounded border border-ziifra-line px-2 py-1 text-xs">
                                                                @foreach ($taxCases as $case)
                                                                    <option value="{{ $case->value }}" @selected(($line['tax_treatment'] ?? 'taxable') === $case->value)>
                                                                        {{ $case->label() }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <select name="items[{{ $item->id }}][allowance_lines][{{ $idx }}][kind]"
                                                                class="rounded border border-ziifra-line px-2 py-1 text-xs">
                                                                @foreach ($kindCases as $case)
                                                                    <option value="{{ $case->value }}" @selected(($line['kind'] ?? 'recurring') === $case->value)>
                                                                        {{ $case->label() }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button"
                                                data-add-payroll-allowance-line
                                                class="mt-1 text-xs font-medium text-ziifra-accent-deep hover:underline">
                                                + {{ __('payroll.add_allowance_line') }}
                                            </button>
                                        </div>
                                    @else
                                        @if ($item->allowanceLines->isEmpty())
                                            <span class="text-ziifra-muted">—</span>
                                        @else
                                            <ul class="space-y-1 text-xs">
                                                @foreach ($item->allowanceLines as $line)
                                                    <li>
                                                        <span class="font-medium text-ziifra-ink">{{ $line->label }}</span>
                                                        <span class="text-ziifra-muted"> · {{ $money((float) $line->amount) }}</span>
                                                        <span class="text-ziifra-muted"> · {{ $line->tax_treatment->label() }}</span>
                                                        <span class="text-ziifra-muted"> · {{ $line->kind->label() }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-right text-ziifra-muted">{{ $money((float) $item->allowances) }}</td>
                                <td class="text-right text-ziifra-muted">{{ $money((float) $item->exempt_allowances_total) }}</td>
                                <td class="text-right font-medium text-ziifra-ink">{{ $money((float) $item->gross_salary) }}</td>
                                <td class="text-right text-ziifra-muted">{{ $money((float) $item->employee_pension) }}</td>
                                <td class="text-right text-ziifra-muted">{{ $money((float) $item->employer_pension) }}</td>
                                <td class="text-right text-ziifra-muted">{{ $money((float) $item->income_tax) }}</td>
                                <td class="text-right font-medium text-ziifra-ink">{{ $money((float) $item->net_salary) }}</td>
                                <td class="text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="whitespace-nowrap">
                                            <a href="{{ $item->payslipUrl() }}" class="text-ziifra-accent-deep hover:underline" target="_blank">
                                                {{ __('payroll.view_payslip') }}
                                            </a>
                                            <span class="mx-1 text-ziifra-muted">·</span>
                                            <a href="{{ $item->payslipPdfUrl() }}" class="text-ziifra-accent-deep hover:underline">
                                                {{ __('payroll.download_pdf') }}
                                            </a>
                                        </span>
                                        @if ($item->payslipRecipientEmail())
                                            <form method="POST" action="{{ route('payroll.payslip.email', [$run, $item]) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="cursor-pointer border-0 bg-transparent p-0 text-xs font-medium text-ziifra-accent-deep hover:underline">
                                                    {{ __('payroll.email_payslip') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-ziifra-muted">{{ __('payroll.email_unavailable') }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="ziifra-payroll-detail-sticky font-semibold text-ziifra-ink">{{ __('payroll.totals') }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['base_gross']) }}</td>
                            <td></td>
                            <td class="text-right font-semibold">{{ $money($totals['allowances']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['exempt_allowances']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['gross']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['employee_pension']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['employer_pension']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['income_tax']) }}</td>
                            <td class="text-right font-semibold">{{ $money($totals['net']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>

    @if ($run->isDraft())
        <div class="ziifra-payroll-detail-footer">
            <button type="submit" class="ziifra-btn-primary">{{ __('payroll.save_draft') }}</button>
        </div>
        </form>

        @can('lock', $run)
            <form method="POST" action="{{ route('payroll.lock', $run) }}" class="mt-4"
                data-confirm="{{ __('payroll.lock_confirm') }}"
                data-confirm-accept="{{ __('payroll.lock_run') }}">
                @csrf
                <button type="submit" class="ziifra-btn-app !bg-emerald-600 !text-white hover:!bg-emerald-700">
                    {{ __('payroll.lock_run') }}
                </button>
            </form>
        @endcan
    @endif
</div>
@endsection
