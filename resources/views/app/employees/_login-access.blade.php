@php
    use App\Enums\EmployeeLoginStatus;

    $embedded = $embedded ?? false;
    $helpKey = match ($loginStatus) {
        EmployeeLoginStatus::Active => 'employees.workspace_access_help_active',
        EmployeeLoginStatus::PendingInvitation => 'employees.workspace_access_help_pending',
        EmployeeLoginStatus::NoEmail => 'employees.workspace_access_help_no_email',
        default => 'employees.workspace_access_help',
    };
    $showActions = $canActivateLogin && in_array($loginStatus, [
        EmployeeLoginStatus::NotActivated,
        EmployeeLoginStatus::PendingInvitation,
        EmployeeLoginStatus::NoEmail,
    ], true);
@endphp

@if ($embedded)
    <div class="ziifra-employee-access ziifra-employee-access--embedded">
        @include('app.employees._login-access-card', [
            'helpKey' => $helpKey,
            'showActions' => $showActions,
            'showTitle' => true,
        ])
    </div>
@else
    <section class="ziifra-employee-access mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        @include('app.employees._login-access-card', [
            'helpKey' => $helpKey,
            'showActions' => $showActions,
            'showTitle' => true,
        ])
    </section>
@endif
