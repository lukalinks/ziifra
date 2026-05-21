<x-mail::message>
# Leave request {{ strtolower($status) }}

Your **{{ $leaveType }}** request ({{ $startDate }} – {{ $endDate }}, {{ $days }} days) was **{{ strtolower($status) }}** by {{ $reviewerName }}.

@if ($rejectionReason)
**Reason:** {{ $rejectionReason }}
@endif

<x-mail::button :url="$viewUrl">
View details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
