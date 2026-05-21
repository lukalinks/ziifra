<x-mail::message>
# Welcome to ZIIFRA, {{ $userName }}

Your workspace **{{ $organizationName }}** is ready. You can sign in anytime to manage employees, leave, and team access.

<x-mail::button :url="$workspaceUrl">
Open your workspace
</x-mail::button>

**Suggested next steps**

- Complete your [company profile]({{ $settingsUrl }}) (legal name, address, fiscal number)
- Add your first employees
- Invite HR or managers to your team

If you did not create this account, please contact us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
