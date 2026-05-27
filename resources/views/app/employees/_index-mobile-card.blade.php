@php
    $loginStatus = $employee->user_id
        ? \App\Enums\EmployeeLoginStatus::Active
        : (blank($employee->email)
            ? \App\Enums\EmployeeLoginStatus::NoEmail
            : (isset($pendingLoginInvites[strtolower($employee->email)])
                ? \App\Enums\EmployeeLoginStatus::PendingInvitation
                : \App\Enums\EmployeeLoginStatus::NotActivated));
@endphp

<x-mobile.list-card :href="route('employees.show', $employee)" :avatar="$employee->initials()">
    <span class="flex items-start justify-between gap-2">
        <span class="min-w-0">
            <span class="block truncate font-semibold text-ziifra-ink">{{ $employee->fullName() }}</span>
            @if ($employee->employee_code)
                <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $employee->employee_code }}</span>
            @elseif ($employee->email)
                <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $employee->email }}</span>
            @endif
        </span>
    </span>

    <span class="ziifra-list-card-meta">
        @if ($employee->department)
            <span class="ziifra-list-card-tag">{{ $employee->department->name }}</span>
        @endif
        @if ($employee->position)
            <span class="ziifra-list-card-tag">{{ $employee->position->title }}</span>
        @endif
        @if ($employee->employment_type)
            <span class="ziifra-list-card-tag">{{ $employee->employment_type->label() }}</span>
        @endif
    </span>

    <span class="ziifra-list-card-badges">
        <span @class([
            'ziifra-list-badge',
            'ziifra-list-badge-success' => $employee->employment_status === \App\Enums\EmploymentStatus::Active,
            'ziifra-list-badge-warning' => $employee->employment_status === \App\Enums\EmploymentStatus::OnLeave,
            'ziifra-list-badge-muted' => ! in_array($employee->employment_status, [\App\Enums\EmploymentStatus::Active, \App\Enums\EmploymentStatus::OnLeave], true),
        ])>{{ $employee->employment_status->label() }}</span>
        <span @class([
            'ziifra-list-badge',
            'ziifra-list-badge-success' => $loginStatus === \App\Enums\EmployeeLoginStatus::Active,
            'ziifra-list-badge-warning' => $loginStatus === \App\Enums\EmployeeLoginStatus::PendingInvitation,
            'ziifra-list-badge-muted' => ! in_array($loginStatus, [\App\Enums\EmployeeLoginStatus::Active, \App\Enums\EmployeeLoginStatus::PendingInvitation], true),
        ])>{{ $loginStatus->label() }}</span>
    </span>
</x-mobile.list-card>
