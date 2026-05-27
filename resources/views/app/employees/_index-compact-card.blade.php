@php
    $loginStatus = $employee->user_id
        ? \App\Enums\EmployeeLoginStatus::Active
        : (blank($employee->email)
            ? \App\Enums\EmployeeLoginStatus::NoEmail
            : (isset($pendingLoginInvites[strtolower($employee->email)])
                ? \App\Enums\EmployeeLoginStatus::PendingInvitation
                : \App\Enums\EmployeeLoginStatus::NotActivated));
@endphp

<article class="ziifra-employee-compact-card">
    <a href="{{ route('employees.show', $employee) }}" class="ziifra-employee-compact-card-main" data-page-nav>
        <span class="ziifra-employee-compact-card-avatar" aria-hidden="true">{{ $employee->initials() }}</span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $employee->fullName() }}</span>
            <span class="mt-0.5 block truncate text-xs text-ziifra-muted">
                {{ $employee->position?->title ?? $employee->department?->name ?? ($employee->email ?: '—') }}
            </span>
            <span class="mt-2 flex flex-wrap gap-1">
                <span @class([
                    'ziifra-list-badge !text-[0.6rem]',
                    'ziifra-list-badge-success' => $employee->employment_status === \App\Enums\EmploymentStatus::Active,
                    'ziifra-list-badge-warning' => $employee->employment_status === \App\Enums\EmploymentStatus::OnLeave,
                    'ziifra-list-badge-muted' => ! in_array($employee->employment_status, [\App\Enums\EmploymentStatus::Active, \App\Enums\EmploymentStatus::OnLeave], true),
                ])>{{ $employee->employment_status->label() }}</span>
            </span>
        </span>
    </a>
    <div class="ziifra-employee-compact-card-actions">
        <a href="{{ route('employees.show', $employee) }}" class="ziifra-employee-compact-card-link" data-page-nav>{{ __('employees.view') }}</a>
        @if ($canManage ?? false)
            <a href="{{ route('employees.edit', $employee) }}" class="ziifra-employee-compact-card-link" data-page-nav>{{ __('employees.edit') }}</a>
        @endif
    </div>
</article>
