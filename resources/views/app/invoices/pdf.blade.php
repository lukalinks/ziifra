<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .title { font-size: 22px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        .totals { margin-top: 20px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">{{ $organization->name }}</div>
            @if ($organization->legal_name)<div>{{ $organization->legal_name }}</div>@endif
        </div>
        <div style="text-align:right">
            <div><strong>{{ __('invoices.invoice') }}</strong> {{ $invoice->invoice_number }}</div>
            <div>{{ $invoice->issue_date->format('M j, Y') }}</div>
        </div>
    </div>

    <p><strong>{{ __('invoices.client') }}:</strong> {{ $invoice->client_name }}</p>
    <p><strong>{{ __('invoices.title') }}:</strong> {{ $invoice->title }}</p>

    @if (! empty($invoice->line_items))
        <table>
            <thead>
                <tr>
                    <th>{{ __('invoices.export.employee') }}</th>
                    <th>{{ __('invoices.export.hours') }}</th>
                    <th>{{ __('invoices.export.rate') }}</th>
                    <th>{{ __('invoices.export.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->line_items as $line)
                    <tr>
                        <td>{{ $line['employee_name'] ?? '' }}</td>
                        <td>{{ $line['hours'] ?? '' }}</td>
                        <td>{{ $line['hourly_rate'] ?? '' }}</td>
                        <td>{{ $line['amount'] ?? '' }} {{ $invoice->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="totals">
        <div>{{ __('invoices.amount') }}: {{ $invoice->amount }} {{ $invoice->currency }}</div>
        <div><strong>{{ __('invoices.total') }}: {{ $invoice->formattedTotal() }}</strong></div>
    </div>
</body>
</html>
