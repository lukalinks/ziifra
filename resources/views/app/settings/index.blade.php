@extends('layouts.app')

@section('title', 'Settings')
@section('header', 'Settings')

@section('content')
<p class="text-sm text-ziifra-muted">Manage {{ $organization->name }} workspace configuration.</p>

<div class="mt-8 grid gap-4 sm:grid-cols-2">
    @if ($canManageBilling ?? false)
        <a href="{{ route('settings.billing') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">Billing & plan</h2>
            <p class="mt-2 text-sm text-ziifra-muted">Subscription, trial status, and employee limits.</p>
        </a>
    @endif

    @if ($canManageOrganization)
        <a href="{{ route('settings.company.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">Company</h2>
            <p class="mt-2 text-sm text-ziifra-muted">Legal details, address, regional defaults, logo, and brand colors.</p>
            @if (! $organization->isProfileComplete())
                <p class="mt-3 text-xs font-medium text-amber-700">Profile incomplete</p>
            @endif
        </a>

        @if ($canManageContractTemplates ?? false)
            <a href="{{ route('settings.contract-templates.index') }}"
                class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.templates.settings.title') }}</h2>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('documents.templates.settings.card') }}</p>
            </a>
        @endif
    @endif

    @if ($canManageEmployees)
        <a href="{{ route('settings.departments.index') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">Departments</h2>
            <p class="mt-2 text-sm text-ziifra-muted">Organize employees into teams and reporting groups.</p>
        </a>

        <a href="{{ route('settings.positions.index') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">Positions</h2>
            <p class="mt-2 text-sm text-ziifra-muted">Job titles used when assigning roles to employees.</p>
        </a>

        @if ($canManageEmployeeFieldDefinitions ?? false)
            <a href="{{ route('settings.employee-fields.index') }}"
                class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
                <h2 class="text-lg font-semibold text-ziifra-ink">Custom fields</h2>
                <p class="mt-2 text-sm text-ziifra-muted">Extra data fields on employee profiles (text, dates, files, and more).</p>
            </a>
        @endif

        @if ($canManageLeave ?? false)
            <a href="{{ route('settings.leave-types.index') }}"
                class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
                <h2 class="text-lg font-semibold text-ziifra-ink">Leave types</h2>
                <p class="mt-2 text-sm text-ziifra-muted">Annual, sick, and other leave categories with yearly allowances.</p>
            </a>
        @endif
    @endif
</div>
@endsection
