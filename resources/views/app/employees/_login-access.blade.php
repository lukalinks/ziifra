@php
    use App\Enums\EmployeeLoginStatus;
    $embedded = $embedded ?? false;
@endphp

<section @class(['ziifra-dashboard-panel' => $embedded, 'mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6' => ! $embedded])>
    @if ($embedded)
        <div class="ziifra-dashboard-panel-head">
            <div>
                <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.workspace_access') }}</h2>
                <p class="text-xs text-ziifra-muted">{{ __('employees.workspace_access_help') }}</p>
            </div>
        </div>
        <div class="p-4 sm:p-5">
    @else
        <h3 class="text-lg font-semibold text-ziifra-ink">{{ __('employees.workspace_access') }}</h3>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('employees.workspace_access_help') }}</p>
    @endif

    <div @class(['ziifra-employee-login-status', 'mt-4' => ! $embedded])>
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
                <p class="mt-2 text-sm text-ziifra-ink">
                    {{ __('employees.login_active_as', [
                        'name' => $employee->user->name,
                        'email' => $employee->user->email,
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                <span class="ziifra-employee-badge ziifra-employee-badge-warning">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm text-ziifra-ink">
                    {{ __('employees.login_invitation_pending', [
                        'email' => $employee->email,
                        'date' => $pendingLoginInvitation?->expires_at?->format('M j, Y') ?? '—',
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::NoEmail)
                <span class="ziifra-employee-badge ziifra-employee-badge-muted">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('employees.login_no_email') }}</p>
            @else
                <span class="ziifra-employee-badge ziifra-employee-badge-muted">{{ $loginStatus->label() }}</span>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('employees.login_not_activated') }}</p>
            @endif
        </div>
    </div>

    @if ($canActivateLogin)
        <div class="ziifra-employee-login-actions">
            @if ($loginStatus === EmployeeLoginStatus::NotActivated)
                <form method="POST" action="{{ route('employees.activate-login', $employee) }}">
                    @csrf
                    <button type="submit" class="ziifra-btn-app w-full justify-center sm:w-auto">
                        {{ __('employees.activate_login') }}
                    </button>
                </form>
            @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                <form method="POST" action="{{ route('employees.resend-login-invitation', $employee) }}">
                    @csrf
                    <button type="submit" class="ziifra-btn-app-outline w-full justify-center sm:w-auto">
                        {{ __('employees.resend_invitation') }}
                    </button>
                </form>
            @elseif ($loginStatus === EmployeeLoginStatus::NoEmail)
                <a href="{{ route('employees.edit', $employee) }}" class="ziifra-btn-app-outline w-full justify-center sm:w-auto" data-page-nav>
                    {{ __('employees.edit_email') }}
                </a>
            @endif
        </div>
    @endif
    @if ($embedded)
        </div>
    @endif
</section>
