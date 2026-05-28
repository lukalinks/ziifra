<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.45; }
        .header { width: 100%; margin-bottom: 26px; }
        .header td { vertical-align: top; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .muted { color: #64748b; }
        .doc-title { font-size: 24px; font-weight: bold; letter-spacing: 0.5px; }
        .parties { width: 100%; margin-bottom: 18px; }
        .parties td { vertical-align: top; width: 50%; }
        .label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        table.items th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; }
        table.items td.num, table.items th.num { text-align: right; }
        .totals { width: 46%; float: right; border-collapse: collapse; margin-top: 16px; }
        .totals td { padding: 6px 8px; }
        .totals td.t-label { color: #475569; }
        .totals td.t-val { text-align: right; font-weight: bold; }
        .totals tr.grand td { border-top: 2px solid #0f172a; font-size: 14px; padding-top: 8px; }
        .footer { clear: both; margin-top: 60px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 11px; color: #475569; }
        .footer .bank { margin-top: 8px; }
        .footer .bank strong { color: #0f172a; }
    </style>
</head>
<body>
    @php
        $currency = $invoice->currency;
        $subtotal = (float) $invoice->amount;
        $taxAmount = (float) $invoice->taxAmount();
        $total = (float) $invoice->totalAmount();
        $bankName = $invoiceSettings['bank_name'] ?? $organization->bank_name;
        $bankIban = $invoiceSettings['bank_iban'] ?? $organization->bank_iban;
        $footerText = $invoiceSettings['footer_text'] ?? null;
    @endphp

    <table class="header">
        <tr>
            <td style="width: 60%;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="logo"><br>
                @endif
                <span class="company-name">{{ $organization->name }}</span>
                @if ($organization->legal_name)<div>{{ $organization->legal_name }}</div>@endif
                @if ($organization->vat_number)<div class="muted">VAT/TVSH: {{ $organization->vat_number }}</div>@endif
                @if ($organization->address_line_1)<div class="muted">{{ $organization->address_line_1 }}</div>@endif
                @if ($organization->city)<div class="muted">{{ trim(($organization->postal_code ? $organization->postal_code.' ' : '').$organization->city) }}</div>@endif
                @if ($organization->email)<div class="muted">{{ $organization->email }}</div>@endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="doc-title">{{ __('invoices.invoice') }}</div>
                <div style="margin-top: 6px;"><strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="muted">{{ __('invoices.issue_date') }}: {{ $invoice->issue_date->format('M j, Y') }}</div>
                <div class="muted">{{ __('invoices.due_date') }}: {{ $invoice->due_date->format('M j, Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <div class="label">{{ __('invoices.client') }}</div>
                <div><strong>{{ $invoice->client_name }}</strong></div>
                @if ($invoice->client_email)<div class="muted">{{ $invoice->client_email }}</div>@endif
            </td>
            <td style="text-align: right;">
                <div class="label">{{ __('invoices.description') }}</div>
                <div>{{ $invoice->title }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('invoices.description') }}</th>
                @if (! empty($invoice->line_items))
                    <th class="num">{{ __('invoices.export.hours') }}</th>
                    <th class="num">{{ __('invoices.export.rate') }}</th>
                @endif
                <th class="num">{{ __('invoices.export.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @if (! empty($invoice->line_items))
                @foreach ($invoice->line_items as $line)
                    <tr>
                        <td>{{ $line['employee_name'] ?? $invoice->title }}</td>
                        <td class="num">{{ $line['hours'] ?? '' }}</td>
                        <td class="num">{{ $line['hourly_rate'] ?? '' }}</td>
                        <td class="num">{{ number_format((float) ($line['amount'] ?? 0), 2) }} {{ $currency }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>{{ $invoice->title }}</td>
                    <td class="num">{{ number_format($subtotal, 2) }} {{ $currency }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="t-label">{{ __('invoices.subtotal') }}</td>
            <td class="t-val">{{ number_format($subtotal, 2) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td class="t-label">{{ __('invoices.tax') }} ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</td>
            <td class="t-val">{{ number_format($taxAmount, 2) }} {{ $currency }}</td>
        </tr>
        <tr class="grand">
            <td class="t-label">{{ __('invoices.total_to_pay') }}</td>
            <td class="t-val">{{ number_format($total, 2) }} {{ $currency }}</td>
        </tr>
    </table>

    <div class="footer">
        @if ($footerText)
            <div>{!! nl2br(e($footerText)) !!}</div>
        @endif
        @if ($invoice->notes)
            <div style="margin-top: 6px;">{{ $invoice->notes }}</div>
        @endif
        @if ($bankName || $bankIban)
            <div class="bank">
                <strong>{{ __('invoices.payment_details') }}</strong><br>
                @if ($bankName){{ __('settings.company.bank_name') }}: {{ $bankName }}<br>@endif
                @if ($bankIban)IBAN: {{ $bankIban }}@endif
            </div>
        @endif
    </div>
</body>
</html>
