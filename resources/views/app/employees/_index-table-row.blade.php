@php
    use App\Enums\EmployeeLoginStatus;
    use App\Enums\EmploymentStatus;

    $loginStatus = $employee->user_id
        ? EmployeeLoginStatus::Active
        : (blank($employee->email)
            ? EmployeeLoginStatus::NoEmail
            : (isset($pendingLoginInvites[strtolower($employee->email)])
                ? EmployeeLoginStatus::PendingInvitation
                : EmployeeLoginStatus::NotActivated));
@endphp

<tr class="hover:bg-ziifra-cream/30">
    <td class="whitespace-nowrap px-4 py-2.5">
        <a href="{{ route('employees.show', $employee) }}" class="font-medium text-ziifra-ink hover:text-ziifra-accent-deep" data-page-nav>
            {{ $employee->fullName() }}
        </a>
    </td>
    <td class="whitespace-nowrap px-4 py-2.5 text-ziifra-muted tabular-nums">{{ $employee->displayCode() }}</td>
    <td class="max-w-[12rem] truncate px-4 py-2.5 text-ziifra-muted">{{ $employee->email ?: '—' }}</td>
    <td class="whitespace-nowrap px-4 py-2.5 text-ziifra-muted">{{ $employee->department?->name ?? '—' }}</td>
    <td class="whitespace-nowrap px-4 py-2.5 text-ziifra-muted">{{ $employee->position?->title ?? '—' }}</td>
    <td class="whitespace-nowrap px-4 py-2.5 text-ziifra-muted">{{ $employee->employment_type?->label() ?? '—' }}</td>
    <td class="whitespace-nowrap px-4 py-2.5">
        <span @class([
            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
            'bg-emerald-50 text-emerald-800' => $employee->employment_status === EmploymentStatus::Active,
            'bg-amber-50 text-amber-900' => $employee->employment_status === EmploymentStatus::OnLeave,
            'bg-ziifra-cream text-ziifra-muted' => ! in_array($employee->employment_status, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true),
        ])>{{ $employee->employment_status?->label() ?? '—' }}</span>
    </td>
    <td class="whitespace-nowrap px-4 py-2.5">
        <span @class([
            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
            'bg-emerald-50 text-emerald-800' => $loginStatus === EmployeeLoginStatus::Active,
            'bg-amber-50 text-amber-900' => $loginStatus === EmployeeLoginStatus::PendingInvitation,
            'bg-ziifra-cream text-ziifra-muted' => ! in_array($loginStatus, [EmployeeLoginStatus::Active, EmployeeLoginStatus::PendingInvitation], true),
        ])>{{ $loginStatus->label() }}</span>
    </td>
    <td class="whitespace-nowrap px-4 py-2.5 text-right">
        <a href="{{ route('employees.show', $employee) }}" class="text-ziifra-accent-deep hover:underline" data-page-nav>{{ __('employees.view') }}</a>
        @if ($canManage ?? false)
            <span class="mx-1 text-ziifra-line">·</span>
            <a href="{{ route('employees.edit', $employee) }}" class="text-ziifra-accent-deep hover:underline" data-page-nav>{{ __('employees.edit') }}</a>
        @endif
    </td>
</tr>
