<x-mail::message>
# Your ZIIFRA trial is ending soon

Your free trial for **{{ $organizationName }}** ends in **{{ $daysRemaining }}** {{ str('day')->plural($daysRemaining) }}.

Choose a plan to keep your team on ZIIFRA without interruption.

<x-mail::button :url="$billingUrl">
View plans & billing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
