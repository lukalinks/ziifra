<x-mail::message>
# Your ZIIFRA trial has ended

The free trial for **{{ $organizationName }}** has ended. Upgrade to a paid plan to continue adding employees and using your workspace.

<x-mail::button :url="$billingUrl">
Choose a plan
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
