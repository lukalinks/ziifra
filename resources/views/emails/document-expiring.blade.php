<x-mail::message>
@if ($isExpired)
# Employee document expired
@else
# Employee document expiring soon
@endif

The document **{{ $documentTitle }}** for **{{ $employeeName }}** @if ($isExpired) expired on **{{ $expiresAt }}**.@else expires on **{{ $expiresAt }}**.@endif

<x-mail::button :url="$profileUrl">
View employee profile
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
