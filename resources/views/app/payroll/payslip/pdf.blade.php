<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    @php
        $currency = $organization->currency ?? 'EUR';
        $money = fn (float $amount) => $currency.' '.number_format($amount, 2);
        $item->loadMissing('allowanceLines');
        $snapshot = $item->employee_snapshot ?? [];
        $layout = $template['layout'] ?? 'standard';
        $baseFs = match ($layout) {
            'compact' => '10px',
            'detailed', 'standard' => '11px',
            default => '11px',
        };
        $hSize = match ($layout) {
            'compact' => '15px',
            default => '18px',
        };
        $legalLines = ($template['show_legal_block'] ?? true) ? $organization->payslipLegalLines() : [];
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: {{ $baseFs }};
            color: #222;
            margin: 24px;
            line-height: 1.45;
        }
        .accent-bar {
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        h1 { margin: 0 0 4px 0; font-size: {{ $hSize }}; }
        .muted { color: #555; font-size: {{ $layout === 'compact' ? '9px' : '10px' }}; }
        table.lines { width: 100%; max-width: 420px; border-collapse: collapse; margin-top: 18px; }
        table.lines td { padding: 6px 4px; border-bottom: 1px solid #ddd; vertical-align: top; }
        table.lines td.amount { text-align: right; white-space: nowrap; font-weight: 600; }
        table.lines td.label { color: #444; }
        .net-row td { border-bottom: none; padding-top: 10px; font-size: {{ $layout === 'compact' ? '11px' : '12px' }}; }
        .employer-note { font-size: 9px; color: #666; font-style: italic; }
        .legal { margin-top: 22px; padding-top: 12px; border-top: 1px solid #eee; }
        .legal ul { margin: 6px 0 0 16px; padding: 0; }
        .legal li { margin-bottom: 2px; }
        .footer { margin-top: 20px; font-size: 9px; color: #666; }
    </style>
</head>
<body>
@if ($logoDataUri)
    <div style="margin-bottom:10px;">
        <img src="{{ $logoDataUri }}" alt="" style="max-height:52px;max-width:200px;">
    </div>
@endif

<div class="accent-bar">
    <p style="margin:0;font-weight:bold;">{{ $appName }}</p>
    @if ($organization->brand_tagline)
        <p class="muted" style="margin:4px 0 0 0;">{{ $organization->brand_tagline }}</p>
    @endif
    <h1>{{ __('payroll.payslip_title') }}</h1>
    <p class="muted" style="margin:6px 0 0 0;">{{ __('payroll.payslip_period', ['period' => $run->periodLabel()]) }}</p>
</div>

<p style="margin:0;"><strong>{{ __('payroll.columns.employee') }}:</strong> {{ $item->employeeName() }}</p>
@if (! empty($snapshot['email']))
    <p class="muted" style="margin:4px 0 0 0;">{{ $snapshot['email'] }}</p>
@endif
@if (! empty($snapshot['department']))
    <p class="muted" style="margin:2px 0 0 0;">{{ __('payroll.snapshot_department') }}: {{ $snapshot['department'] }}</p>
@endif
@if (! empty($snapshot['position']))
    <p class="muted" style="margin:2px 0 0 0;">{{ __('payroll.snapshot_position') }}: {{ $snapshot['position'] }}</p>
@endif

<table class="lines">
    <tr>
        <td class="label">{{ __('payroll.columns.base_gross') }}</td>
        <td class="amount">{{ $money((float) $item->base_gross_salary) }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('payroll.columns.taxable_allowances') }}</td>
        <td class="amount">{{ $money((float) $item->allowances) }}</td>
    </tr>
    @if ($item->allowanceLines->isNotEmpty())
        <tr>
            <td colspan="2" class="label" style="padding-top:8px;">
                <div style="font-weight:bold;font-size:9px;text-transform:uppercase;color:#777;">{{ __('payroll.payslip_allowance_breakdown') }}</div>
                <ul style="margin:6px 0 0 16px;padding:0;font-size:9px;color:#222;">
                    @foreach ($item->allowanceLines as $line)
                        <li style="margin-bottom:2px;">
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
            <td class="label">{{ __('payroll.columns.exempt_allowances') }}</td>
            <td class="amount">{{ $money((float) $item->exempt_allowances_total) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="muted" style="font-style:italic;padding-top:0;">{{ __('payroll.payslip_statutory_exempt_note') }}</td>
        </tr>
    @endif
    <tr>
        <td class="label">{{ __('payroll.columns.taxable_gross') }}</td>
        <td class="amount">{{ $money((float) $item->gross_salary) }}</td>
    </tr>
    @if (($template['show_employer_pension'] ?? false) || ($template['layout'] ?? '') === 'detailed')
        <tr>
            <td class="label">
                {{ __('payroll.columns.employer_pension') }}
                <div class="employer-note">{{ __('payroll.employer_contribution_note') }}</div>
            </td>
            <td class="amount">{{ $money((float) $item->employer_pension) }}</td>
        </tr>
    @endif
    <tr>
        <td class="label">{{ __('payroll.columns.employee_pension') }}</td>
        <td class="amount">− {{ $money((float) $item->employee_pension) }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('payroll.columns.tax') }}</td>
        <td class="amount">− {{ $money((float) $item->income_tax) }}</td>
    </tr>
    <tr class="net-row">
        <td><strong>{{ __('payroll.columns.net') }}</strong></td>
        <td class="amount"><strong>{{ $money((float) $item->net_salary) }}</strong></td>
    </tr>
</table>

@if ($legalLines !== [])
    <div class="legal">
        <p style="margin:0;font-weight:bold;font-size:10px;text-transform:uppercase;color:#777;">{{ __('payroll.payslip_employer_section') }}</p>
        <ul>
            @foreach ($legalLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (! empty($template['footer_note']))
    <p class="footer" style="margin-top:16px;"><strong>{{ __('payroll.payslip_footer_note_label') }}</strong> {{ $template['footer_note'] }}</p>
@endif

<p class="footer">{{ __('payroll.rules_notice') }}</p>
</body>
</html>
