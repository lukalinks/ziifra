<x-mail::message>
# You are invited to {{ $organizationName }}

You have been invited to join **{{ $organizationName }}** on ZIIFRA as **{{ $role }}**.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This link expires in 7 days.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
