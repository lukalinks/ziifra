<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll &amp; Time</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { width: 100%; margin-bottom: 16px; }
        .header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 180px; }
        .company { font-size: 16px; font-weight: bold; }
        .muted { color: #666; }
        h1 { font-size: 15px; margin: 4px 0 2px; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid th, table.grid td { border: 1px solid #ccc; padding: 5px 6px; }
        table.grid th { background: #f2efe9; text-align: left; }
        table.grid td.num, table.grid th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f7f5f0; }
        .totals { margin-top: 14px; width: 45%; float: right; border-collapse: collapse; }
        .totals td { padding: 4px 8px; border-bottom: 1px solid #e3e0d8; }
        .totals td.label { color: #555; }
        .totals td.val { text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 60%;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="logo"><br>
                @endif
                <span class="company">{{ $organization->name }}</span>
                @if ($organization->vat_number)
                    <div class="muted">VAT/TVSH: {{ $organization->vat_number }}</div>
                @endif
                @if ($organization->address_line_1)
                    <div class="muted">{{ $organization->address_line_1 }}{{ $organization->city ? ', '.$organization->city : '' }}</div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <h1>Payroll &amp; Time</h1>
                <div class="muted">{{ $year }}@if($month) / {{ \Carbon\Carbon::create(null, $month)->format('F') }}@endif</div>
                @if ($project)
                    <div class="muted">Project: {{ $project->name }}</div>
                @endif
                <div class="muted">Trust: {{ $trustEmployeePct }}% employee + {{ $trustEmployerPct }}% company</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Code</th>
                <th class="num">Hours</th>
                <th class="num">Rate/h</th>
                <th class="num">Gross</th>
                <th class="num">Trust (emp.)</th>
                <th class="num">Trust (co.)</th>
                <th class="num">Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['employee']->fullName() }}</td>
                    <td>{{ $row['employee']->displayCode() }}</td>
                    <td class="num">{{ number_format($row['total_hours'], 1) }}</td>
                    <td class="num">{{ number_format($row['hourly_rate'], 2) }}</td>
                    <td class="num">{{ number_format($row['gross'], 2) }}</td>
                    <td class="num">{{ number_format($row['trust_employee'], 2) }}</td>
                    <td class="num">{{ number_format($row['trust_employer'], 2) }}</td>
                    <td class="num">{{ number_format($row['net'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="num">{{ number_format($totals['hours'], 1) }}</td>
                <td class="num"></td>
                <td class="num">{{ number_format($totals['gross'], 2) }}</td>
                <td class="num">{{ number_format($totals['trust_employee'], 2) }}</td>
                <td class="num">{{ number_format($totals['trust_employer'], 2) }}</td>
                <td class="num">{{ number_format($totals['net'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="totals">
        <tr><td class="label">Gross total</td><td class="val">{{ number_format($totals['gross'], 2) }}</td></tr>
        <tr><td class="label">Trust (employee {{ $trustEmployeePct }}%)</td><td class="val">{{ number_format($totals['trust_employee'], 2) }}</td></tr>
        <tr><td class="label">Trust (company {{ $trustEmployerPct }}%)</td><td class="val">{{ number_format($totals['trust_employer'], 2) }}</td></tr>
        @if ($vatPct > 0)
            <tr><td class="label">TVSH ({{ $vatPct }}%)</td><td class="val">{{ number_format($totals['gross'] * $vatPct / 100, 2) }}</td></tr>
        @endif
        <tr><td class="label">Net payable</td><td class="val">{{ number_format($totals['net'], 2) }}</td></tr>
    </table>
</body>
</html>
