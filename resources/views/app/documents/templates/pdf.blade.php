<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 36px 42px;
            line-height: 1.55;
        }
        .header {
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .logo { max-height: 48px; max-width: 160px; margin-bottom: 8px; }
        h1 { margin: 0 0 6px 0; font-size: 18px; color: {{ $primaryColor }}; }
        h2 { margin: 20px 0 8px 0; font-size: 13px; color: {{ $primaryColor }}; }
        p { margin: 0 0 10px 0; }
        .meta { color: #555; font-size: 10px; }
        .parties { width: 100%; border-collapse: collapse; margin: 16px 0 20px 0; }
        .parties td { vertical-align: top; width: 50%; padding: 8px 12px 8px 0; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #666; margin-bottom: 4px; }
        .signatures { margin-top: 36px; width: 100%; }
        .signatures td { width: 50%; vertical-align: top; padding-top: 24px; }
        .line { border-top: 1px solid #999; margin-top: 48px; padding-top: 6px; font-size: 10px; }
        .blank-note {
            background: #f7f5f0;
            border: 1px dashed #ccc;
            padding: 8px 10px;
            margin-bottom: 16px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="" class="logo">
        @endif
        <h1>{{ $fields['template_title'] }}</h1>
        <p class="meta">{{ $fields['company_name'] }} · {{ __('documents.templates.pdf_date', ['date' => $fields['contract_date']]) }}</p>
    </div>

    @if ($isBlank)
        <div class="blank-note">{{ __('documents.templates.blank_notice') }}</div>
    @endif

    <table class="parties">
        <tr>
            <td>
                <div class="label">{{ __('documents.templates.employer') }}</div>
                <strong>{{ $fields['company_name'] }}</strong><br>
                {{ $fields['company_address'] }}<br>
                @if ($fields['company_registration'] !== '—')
                    {{ __('documents.templates.registration') }}: {{ $fields['company_registration'] }}<br>
                @endif
                @if ($fields['company_fiscal'] !== '—')
                    {{ __('documents.templates.fiscal') }}: {{ $fields['company_fiscal'] }}
                @endif
            </td>
            <td>
                <div class="label">{{ __('documents.templates.employee') }}</div>
                <strong>{{ $fields['employee_name'] }}</strong><br>
                {{ $fields['employee_email'] }}<br>
                {{ $fields['employee_phone'] }}
            </td>
        </tr>
    </table>

    {!! $bodyHtml !!}

    <table class="signatures">
        <tr>
            <td>
                <div class="line">
                    {{ $fields['signatory_name'] }}<br>
                    <span class="meta">{{ $fields['signatory_title'] }} · {{ $fields['company_name'] }}</span>
                </div>
            </td>
            <td>
                <div class="line">
                    {{ $fields['employee_name'] }}<br>
                    <span class="meta">{{ __('documents.templates.employee_signature') }}</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
