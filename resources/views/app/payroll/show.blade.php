@extends('layouts.app')

@section('title', $run->periodLabel())
@section('header', $run->periodLabel())

@section('content')
@php
    $currency = $organization->currency ?? 'EUR';
    $money = fn (float $amount) => $currency.' '.number_format($amount, 2);
    $taxCases = \App\Enums\AllowanceTaxTreatment::cases();
    $kindCases = \App\Enums\PayrollAllowanceKind::cases();
@endphp

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $run->isLocked() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">
            {{ $run->status->label() }}
        </span>
        @if ($run->isLocked() && $run->locked_at)
            <p class="mt-2 text-sm text-ziifra-muted">
                Locked {{ $run->locked_at->format('d M Y H:i') }}
                @if ($run->lockedBy)
                    by {{ $run->lockedBy->name }}
                @endif
            </p>
        @endif
    </div>
    <a href="{{ route('payroll.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">← {{ __('payroll.title') }}</a>
</div>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <p class="text-sm text-ziifra-muted">{{ __('payroll.rules_notice') }}</p>
    @if ($run->items->isNotEmpty())
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('payroll.export-pdfs', $run) }}"
                class="shrink-0 text-sm font-medium text-ziifra-accent-deep hover:underline">
                {{ __('payroll.download_all_pdf') }}
            </a>
            <form method="POST" action="{{ route('payroll.email-payslips', $run) }}" class="inline"
                data-confirm="{{ __('payroll.email_confirm_all') }}"
                data-confirm-accept="{{ __('payroll.email_all_payslips') }}">
                @csrf
                <button type="submit"
                    class="shrink-0 cursor-pointer border-0 bg-transparent p-0 text-sm font-medium text-ziifra-accent-deep hover:underline">
                    {{ __('payroll.email_all_payslips') }}
                </button>
            </form>
        </div>
    @endif
</div>

@if ($run->isDraft())
    <form method="POST" action="{{ route('payroll.update', $run) }}">
        @csrf
        @method('PUT')
@endif

<div class="overflow-x-auto rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    <table class="min-w-full divide-y divide-ziifra-line/80 text-sm">
        <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
            <tr>
                <th class="px-4 py-3">{{ __('payroll.columns.employee') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.base_gross') }}</th>
                <th class="px-4 py-3 min-w-[14rem]">{{ __('payroll.columns.allowance_lines') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.taxable_allowances') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.exempt_allowances') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.taxable_gross') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.employee_pension') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.employer_pension') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.tax') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.net') }}</th>
                <th class="px-4 py-3 text-right">{{ __('payroll.columns.payslip') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ziifra-line/60">
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
                <tr class="align-top">
                    <td class="px-4 py-3 font-medium text-ziifra-ink">{{ $item->employeeName() }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($run->isDraft())
                            <input type="number" step="0.01" min="0"
                                name="items[{{ $item->id }}][base_gross_salary]"
                                value="{{ old('items.'.$item->id.'.base_gross_salary', $item->base_gross_salary) }}"
                                class="w-28 rounded-lg border border-ziifra-line px-2 py-1 text-right text-sm">
                        @else
                            {{ $money((float) $item->base_gross_salary) }}
                        @endif
                    </td>
                    <td class="px-4 py-3">
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
                    <td class="px-4 py-3 text-right text-ziifra-muted">{{ $money((float) $item->allowances) }}</td>
                    <td class="px-4 py-3 text-right text-ziifra-muted">{{ $money((float) $item->exempt_allowances_total) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-ziifra-ink">{{ $money((float) $item->gross_salary) }}</td>
                    <td class="px-4 py-3 text-right text-ziifra-muted">{{ $money((float) $item->employee_pension) }}</td>
                    <td class="px-4 py-3 text-right text-ziifra-muted">{{ $money((float) $item->employer_pension) }}</td>
                    <td class="px-4 py-3 text-right text-ziifra-muted">{{ $money((float) $item->income_tax) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-ziifra-ink">{{ $money((float) $item->net_salary) }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex flex-col items-end gap-1 sm:flex-row sm:flex-wrap sm:justify-end sm:gap-x-2">
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
                                        class="cursor-pointer border-0 bg-transparent p-0 text-sm font-medium text-ziifra-accent-deep hover:underline">
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
        <tfoot class="bg-ziifra-cream/30 font-medium text-ziifra-ink">
            <tr>
                <td class="px-4 py-3">{{ __('payroll.totals') }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['base_gross']) }}</td>
                <td class="px-4 py-3"></td>
                <td class="px-4 py-3 text-right">{{ $money($totals['allowances']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['exempt_allowances']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['gross']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['employee_pension']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['employer_pension']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['income_tax']) }}</td>
                <td class="px-4 py-3 text-right">{{ $money($totals['net']) }}</td>
                <td class="px-4 py-3"></td>
            </tr>
        </tfoot>
    </table>
</div>

@if ($run->isDraft())
        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="ziifra-btn-primary">{{ __('payroll.save_draft') }}</button>
        </div>
    </form>

    @can('lock', $run)
        <form method="POST" action="{{ route('payroll.lock', $run) }}" class="mt-4"
            data-confirm="{{ __('payroll.lock_confirm') }}"
            data-confirm-accept="{{ __('payroll.lock_run') }}">
            @csrf
            <button type="submit" class="rounded-lg border border-emerald-600 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-900 hover:bg-emerald-100">
                {{ __('payroll.lock_run') }}
            </button>
        </form>
    @endcan
@endif
@endsection
