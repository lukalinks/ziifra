<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('payroll.payslip_title') }} — {{ $item->employeeName() }}</title>
    @vite(['resources/css/app.css'])
    @php
        $currency = $organization->currency ?? 'EUR';
        $money = fn (float $amount) => $currency.' '.number_format($amount, 2);
        $item->loadMissing('allowanceLines');
        $snapshot = $item->employee_snapshot ?? [];
        $layout = $template['layout'] ?? 'standard';
        $legalLines = ($template['show_legal_block'] ?? true) ? $organization->payslipLegalLines() : [];
        $showEmployerRow = (($template['show_employer_pension'] ?? false) || $layout === 'detailed');
    @endphp
    <style>
        .payslip-accent { border-bottom: 3px solid {{ $primaryColor }}; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-ziifra-paper p-8 text-ziifra-ink {{ $layout === 'compact' ? 'text-sm' : '' }}">
    <div class="no-print mb-6 flex flex-wrap gap-3">
        <button type="button" onclick="window.print()" class="ziifra-btn-primary">{{ __('payroll.print_payslip') }}</button>
        <a href="{{ $item->payslipPdfUrl() }}" class="ziifra-btn-primary">{{ __('payroll.download_pdf') }}</a>
        @if ($item->payslipRecipientEmail())
            <form method="POST" action="{{ route('payroll.payslip.email', [$run, $item]) }}" class="inline">
                @csrf
                <button type="submit" class="ziifra-btn-primary">{{ __('payroll.email_payslip') }}</button>
            </form>
        @endif
        <a href="{{ $run->showUrl() }}" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium hover:bg-ziifra-cream">Back</a>
    </div>

    @if ($logoDataUri)
        <div class="mb-4">
            <img src="{{ $logoDataUri }}" alt="" class="max-h-14 max-w-[200px] object-contain">
        </div>
    @endif

    <header class="payslip-accent pb-4">
        <p class="text-sm font-semibold">{{ $appName }}</p>
        @if ($organization->brand_tagline)
            <p class="text-xs text-ziifra-muted">{{ $organization->brand_tagline }}</p>
        @endif
        <h1 class="{{ $layout === 'compact' ? 'text-xl' : 'text-2xl' }} mt-2 font-semibold">{{ __('payroll.payslip_title') }}</h1>
        <p class="text-sm text-ziifra-muted">{{ __('payroll.payslip_period', ['period' => $run->periodLabel()]) }}</p>
    </header>

    <section class="mt-6 grid gap-1 text-sm">
        <p><span class="text-ziifra-muted">{{ __('payroll.columns.employee') }}:</span> <strong>{{ $item->employeeName() }}</strong></p>
        @if (! empty($snapshot['email']))
            <p><span class="text-ziifra-muted">{{ __('payroll.snapshot_email') }}:</span> {{ $snapshot['email'] }}</p>
        @endif
        @if (! empty($snapshot['department']))
            <p><span class="text-ziifra-muted">{{ __('payroll.snapshot_department') }}:</span> {{ $snapshot['department'] }}</p>
        @endif
        @if (! empty($snapshot['position']))
            <p><span class="text-ziifra-muted">{{ __('payroll.snapshot_position') }}:</span> {{ $snapshot['position'] }}</p>
        @endif
    </section>

    <table class="mt-8 w-full max-w-md text-sm">
        <tbody class="divide-y divide-ziifra-line">
            <tr>
                <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.base_gross') }}</td>
                <td class="py-2 text-right font-medium">{{ $money((float) $item->base_gross_salary) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.taxable_allowances') }}</td>
                <td class="py-2 text-right font-medium">{{ $money((float) $item->allowances) }}</td>
            </tr>
            @if ($item->allowanceLines->isNotEmpty())
                <tr>
                    <td colspan="2" class="py-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('payroll.payslip_allowance_breakdown') }}</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-ziifra-ink">
                            @foreach ($item->allowanceLines as $line)
                                <li>
                                    {{ $line->label }}
                                    · {{ $money((float) $line->amount) }}
                                    · {{ $line->tax_treatment->label() }}
                                    · {{ $line->kind->label() }}
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
            @if ((float) $item->exempt_allowances_total > 0)
                <tr>
                    <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.exempt_allowances') }}</td>
                    <td class="py-2 text-right font-medium">{{ $money((float) $item->exempt_allowances_total) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="pb-2 pt-0 text-xs italic text-ziifra-muted">{{ __('payroll.payslip_statutory_exempt_note') }}</td>
                </tr>
            @endif
            <tr>
                <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.taxable_gross') }}</td>
                <td class="py-2 text-right font-medium">{{ $money((float) $item->gross_salary) }}</td>
            </tr>
            @if ($showEmployerRow)
                <tr>
                    <td class="py-2 text-ziifra-muted">
                        {{ __('payroll.columns.employer_pension') }}
                        <p class="mt-0.5 text-xs italic text-ziifra-muted">{{ __('payroll.employer_contribution_note') }}</p>
                    </td>
                    <td class="py-2 text-right">{{ $money((float) $item->employer_pension) }}</td>
                </tr>
            @endif
            <tr>
                <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.employee_pension') }}</td>
                <td class="py-2 text-right">− {{ $money((float) $item->employee_pension) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-ziifra-muted">{{ __('payroll.columns.tax') }}</td>
                <td class="py-2 text-right">− {{ $money((float) $item->income_tax) }}</td>
            </tr>
            <tr>
                <td class="py-2 font-semibold">{{ __('payroll.columns.net') }}</td>
                <td class="py-2 text-right text-lg font-semibold">{{ $money((float) $item->net_salary) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($legalLines !== [])
        <section class="mt-8 border-t border-ziifra-line/80 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('payroll.payslip_employer_section') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ziifra-ink">
                @foreach ($legalLines as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (! empty($template['footer_note']))
        <p class="mt-6 text-sm text-ziifra-ink"><span class="font-medium">{{ __('payroll.payslip_footer_note_label') }}</span> {{ $template['footer_note'] }}</p>
    @endif

    <p class="mt-8 text-xs text-ziifra-muted">{{ __('payroll.rules_notice') }}</p>
</body>
</html>
