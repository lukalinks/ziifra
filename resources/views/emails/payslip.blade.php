<x-mail::message>
# {{ __('payroll.payslip_title', [], $locale) }}

{{ $intro }}

@if ($sentByLine)
{{ $sentByLine }}
@endif

{{ $attachmentNote }}

{{ $footerNotice }}

{{ __('payroll.email_thanks', [], $locale) }}<br>
{{ config('app.name') }}
</x-mail::message>
