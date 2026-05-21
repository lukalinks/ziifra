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
        <div class="p-5">
    @else
        <h3 class="text-lg font-semibold text-ziifra-ink">{{ __('employees.workspace_access') }}</h3>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('employees.workspace_access_help') }}</p>
    @endif

    <div @class(['flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between', 'mt-4' => ! $embedded])>
        <div class="flex-1">
            @if ($loginStatus === EmployeeLoginStatus::Active)
                <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                    {{ $loginStatus->label() }}
                </span>
                <p class="mt-2 text-sm text-ziifra-ink">
                    {{ __('employees.login_active_as', [
                        'name' => $employee->user->name,
                        'email' => $employee->user->email,
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
                    {{ $loginStatus->label() }}
                </span>
                <p class="mt-2 text-sm text-ziifra-ink">
                    {{ __('employees.login_invitation_pending', [
                        'email' => $employee->email,
                        'date' => $pendingLoginInvitation?->expires_at?->format('M j, Y') ?? '—',
                    ]) }}
                </p>
            @elseif ($loginStatus === EmployeeLoginStatus::NoEmail)
                <span class="inline-flex rounded-full bg-ziifra-cream px-2.5 py-1 text-xs font-medium text-ziifra-muted">
                    {{ $loginStatus->label() }}
                </span>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('employees.login_no_email') }}</p>
            @else
                <span class="inline-flex rounded-full bg-ziifra-cream px-2.5 py-1 text-xs font-medium text-ziifra-ink">
                    {{ $loginStatus->label() }}
                </span>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('employees.login_not_activated') }}</p>
            @endif
        </div>

        @if ($canActivateLogin)
            <div class="flex flex-wrap gap-2">
                @if ($loginStatus === EmployeeLoginStatus::NotActivated)
                    <form method="POST" action="{{ route('employees.activate-login', $employee) }}">
                        @csrf
                        <button type="submit" class="ziifra-btn-app">
                            {{ __('employees.activate_login') }}
                        </button>
                    </form>
                @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                    <form method="POST" action="{{ route('employees.resend-login-invitation', $employee) }}">
                        @csrf
                        <button type="submit" class="ziifra-btn-app-outline">
                            {{ __('employees.resend_invitation') }}
                        </button>
                    </form>
                @elseif ($loginStatus === EmployeeLoginStatus::NoEmail)
                    <a href="{{ route('employees.edit', $employee) }}" class="ziifra-btn-app-outline">
                        {{ __('employees.edit_email') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
    @if ($embedded)
        </div>
    @endif
</section>
