<x-mail::message>
# New leave request

**{{ $employeeName }}** submitted a **{{ $leaveType }}** request for **{{ $days }}** working day(s), from {{ $startDate }} to {{ $endDate }}.

@if ($submittedBy && $submittedBy !== $employeeName)
Submitted by {{ $submittedBy }}.
@endif

<x-mail::button :url="$reviewUrl">
Review request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
