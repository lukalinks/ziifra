@props(['organization', 'logo' => null])

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;border-collapse:collapse;">
    <tr>
        <td width="55%" valign="top" style="vertical-align:top;padding-right:16px;">
            {{ $slot }}
        </td>
        <td width="45%" valign="top" style="vertical-align:top;text-align:right;">
            @if ($logo)
                <img src="{{ $logo }}" alt="" style="max-height:64px;max-width:220px;margin:0 0 10px auto;display:block;">
            @endif
            <div style="font-size:17px;font-weight:bold;color:#0f172a;line-height:1.3;">{{ $organization->displayName() }}</div>
            @if ($organization->legal_name && $organization->legal_name !== $organization->name)
                <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $organization->name }}</div>
            @endif
            @if ($organization->vat_number)
                <div style="font-size:11px;color:#64748b;margin-top:4px;">VAT: {{ $organization->vat_number }}</div>
            @endif
            @if ($organization->fiscal_number)
                <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $organization->fiscal_number }}</div>
            @endif
            @if ($organization->formattedAddress())
                <div style="font-size:11px;color:#64748b;margin-top:4px;line-height:1.45;">{{ $organization->formattedAddress() }}</div>
            @endif
            @if ($organization->phone)
                <div style="font-size:11px;color:#64748b;margin-top:4px;">{{ $organization->phone }}</div>
            @endif
            @if ($organization->hr_email ?: $organization->email)
                <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $organization->hr_email ?: $organization->email }}</div>
            @endif
        </td>
    </tr>
</table>
