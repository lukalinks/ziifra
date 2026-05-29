@extends('layouts.app')

@section('title', __('settings.title'))
@section('header', __('settings.title'))

@section('content')
<p class="text-sm text-ziifra-muted">{{ __('settings.hub.intro', ['name' => $organization->name]) }}</p>

<div class="mt-8 grid gap-4 sm:grid-cols-2">
    @if ($canManageBilling ?? false)
        <a href="{{ route('settings.billing') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.hub.billing_title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.billing_card') }}</p>
        </a>
    @endif

    @if ($canManageOrganization)
        <a href="{{ route('settings.company.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.hub.company_title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.company_card') }}</p>
        </a>

        <a href="{{ route('settings.payroll.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings_payroll.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings_payroll.card') }}</p>
        </a>

        <a href="{{ route('settings.invoices.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.invoices.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.invoices.card') }}</p>
        </a>

        <a href="{{ route('settings.chat.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.chat.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.chat.card') }}</p>
        </a>

        <a href="{{ route('settings.mail.edit') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.mail.card') }}</p>
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
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.departments.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.departments_card') }}</p>
        </a>

        <a href="{{ route('settings.positions.index') }}"
            class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.positions.title') }}</h2>
            <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.positions_card') }}</p>
        </a>

        @if ($canManageEmployeeFieldDefinitions ?? false)
            <a href="{{ route('settings.employee-fields.index') }}"
                class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.employee_fields.title') }}</h2>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.custom_fields_card') }}</p>
            </a>
        @endif

        @if ($canManageLeave ?? false)
            <a href="{{ route('settings.leave-types.index') }}"
                class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 transition hover:border-ziifra-accent/40 hover:shadow-sm">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.leave_types.title') }}</h2>
                <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.hub.leave_types_card') }}</p>
            </a>
        @endif
    @endif
</div>
@endsection
