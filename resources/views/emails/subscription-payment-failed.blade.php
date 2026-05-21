<x-mail::message>
# Payment failed for your subscription

We could not process the latest payment for **{{ $organizationName }}**. Update your billing details to avoid losing access.

<x-mail::button :url="$billingUrl">
Manage billing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
