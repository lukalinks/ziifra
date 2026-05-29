@php
    use App\Enums\EmployeeLoginStatus;

    $helpKey = $helpKey ?? 'employees.workspace_access_help';
    $showActions = $showActions ?? false;
    $showTitle = $showTitle ?? false;
@endphp

<div class="ziifra-employee-access-card">
    @if ($showTitle)
        <div class="ziifra-employee-access-card-head">
            <h2 class="ziifra-employee-access-title">{{ __('employees.workspace_access') }}</h2>
            <p class="ziifra-employee-access-callout">{{ __($helpKey) }}</p>
        </div>
    @endif

    <div class="ziifra-employee-access-status">
        <div class="ziifra-employee-login-status-icon" aria-hidden="true">
            @if ($loginStatus === EmployeeLoginStatus::Active)
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            @if ($loginStatus === EmployeeLoginStatus::Active)
                <span class="ziifra-employee-badge ziifra-employee-badge-success">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm leading-relaxed text-ziifra-ink">
                    {{ __('employees.login_active_as', [
                        'name' => $employee->user->name,
                        'email' => $employee->user->email,
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                <span class="ziifra-employee-badge ziifra-employee-badge-warning">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm leading-relaxed text-ziifra-ink">
                    {{ __('employees.login_invitation_pending', [
                        'email' => $employee->email,
                        'date' => $pendingLoginInvitation?->expires_at?->format('M j, Y') ?? '—',
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::NoEmail)
                <span class="ziifra-employee-badge ziifra-employee-badge-muted">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm leading-relaxed text-ziifra-ink">{{ __('employees.login_no_email') }}</p>
            @else
                <span class="ziifra-employee-badge ziifra-employee-badge-muted">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm leading-relaxed text-ziifra-ink">{{ __('employees.login_not_activated') }}</p>
            @endif
        </div>
    </div>

    @if ($showActions)
        @include('app.employees._login-access-actions')
    @endif
</div>
