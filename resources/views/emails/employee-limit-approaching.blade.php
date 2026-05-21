<x-mail::message>
# Employee limit almost reached

**{{ $organizationName }}** is using **{{ $employeeCount }}** of **{{ $employeeLimit }}** employees on the current plan. You can add one more employee before you need to upgrade.

<x-mail::button :url="$billingUrl">
Upgrade plan
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
