@extends('layouts.app')

@section('title', 'Employees')
@section('header', 'Employees')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="search" class="sr-only">Search</label>
            <input id="search" name="search" type="search" placeholder="Search by name or email"
                value="{{ request('search') }}"
                class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        </div>
        <div>
            <label for="type" class="sr-only">Type</label>
            <select id="type" name="type" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="sr-only">Status</label>
            <select id="status" name="status" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="department_id" class="sr-only">Department</label>
            <select id="department_id" name="department_id" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($canActivateLogin ?? false)
            <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                <input type="checkbox" name="missing_login" value="1" @checked($filterMissingLogin ?? false)
                    class="rounded border-ziifra-line text-ziifra-accent-deep focus:ring-ziifra-accent">
                {{ __('employees.filter_missing_login') }}
            </label>
        @endif
        <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            Filter
        </button>
    </form>
    @if ($filterMissingLogin ?? false)
        <p class="mt-2 text-sm text-ziifra-muted">{{ __('employees.filter_missing_login_active') }}</p>
    @endif
    @if ($canManage)
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('employees.export') }}" class="ziifra-btn-app-outline">Export CSV</a>
            <a href="{{ route('employees.import') }}" class="ziifra-btn-app-outline">Import CSV</a>
            <a href="{{ route('employees.create') }}" class="ziifra-btn-app">Add employee</a>
        </div>
    @endif
</div>

<div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    @if ($employees->isEmpty())
        <p class="p-8 text-center text-sm text-ziifra-muted">No employees yet.@if ($canManage) <a href="{{ route('employees.create') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">Add your first employee</a>.@endif</p>
    @else
        <table class="min-w-full divide-y divide-ziifra-line/80 text-sm">
            <thead class="bg-ziifra-cream">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Department</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Position</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Manager</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Login</th>
                    <th class="px-4 py-3 text-right font-medium text-ziifra-muted"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($employees as $employee)
                    @php
                        $loginStatus = $employee->user_id
                            ? \App\Enums\EmployeeLoginStatus::Active
                            : (blank($employee->email)
                                ? \App\Enums\EmployeeLoginStatus::NoEmail
                                : (isset($pendingLoginInvites[strtolower($employee->email)])
                                    ? \App\Enums\EmployeeLoginStatus::PendingInvitation
                                    : \App\Enums\EmployeeLoginStatus::NotActivated));
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-ziifra-ink">
                            <a href="{{ route('employees.show', $employee) }}" class="hover:text-ziifra-accent-deep">{{ $employee->fullName() }}</a>
                        </td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $employee->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $employee->position?->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $employee->manager?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $employee->employment_type?->label() ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($employee->employment_status === \App\Enums\EmploymentStatus::Active) bg-green-50 text-green-700
                                @elseif($employee->employment_status === \App\Enums\EmploymentStatus::OnLeave) bg-amber-50 text-amber-700
                                @else bg-ziifra-cream text-ziifra-muted @endif">
                                {{ $employee->employment_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($loginStatus === \App\Enums\EmployeeLoginStatus::Active) bg-green-50 text-green-700
                                @elseif($loginStatus === \App\Enums\EmployeeLoginStatus::PendingInvitation) bg-amber-50 text-amber-800
                                @else bg-ziifra-cream text-ziifra-muted @endif">
                                {{ $loginStatus->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employees.show', $employee) }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">View</a>
                            @if ($canManage)
                                · <a href="{{ route('employees.edit', $employee) }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">Edit</a>
                            @endif
                            @if (($canActivateLogin ?? false) && $loginStatus === \App\Enums\EmployeeLoginStatus::NotActivated)
                                ·
                                <form method="POST" action="{{ route('employees.activate-login', $employee) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">
                                        Activate login
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-ziifra-line/80 px-4 py-3">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
