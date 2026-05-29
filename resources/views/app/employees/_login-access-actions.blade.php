@php
    use App\Enums\EmployeeLoginStatus;
@endphp

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
