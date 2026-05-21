@extends('layouts.app')

@section('title', __('settings.company.title'))
@section('header', __('settings.company.title'))

@section('content')
<p class="mb-6">
    <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('settings.back') }}</a>
</p>

@if (! $organization->isProfileComplete())
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">{{ __('settings.company.complete_profile') }}</p>
        <p class="mt-1 text-amber-800">{{ __('settings.company.complete_profile_hint') }}</p>
    </div>
@endif

@php $selectedWorkDays = old('work_week_days', $organization->workWeekDayValues()); @endphp

<form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-8">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.identity') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.identity_help') }}</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.display_name') }} <span class="text-red-600">*</span></label>
                <input id="name" name="name" type="text" required value="{{ old('name', $organization->name) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="legal_name" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.legal_name') }}</label>
                <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name', $organization->legal_name) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="{{ __('settings.company.legal_name_placeholder') }}">
                @error('legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="legal_form" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.legal_form') }}</label>
                <select id="legal_form" name="legal_form" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    <option value="">{{ __('settings.company.select') }}</option>
                    @foreach ($legalForms as $form)
                        <option value="{{ $form->value }}" @selected(old('legal_form', $organization->legal_form?->value) === $form->value)>
                            {{ $form->label() }}
                        </option>
                    @endforeach
                </select>
                @error('legal_form')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="registration_number" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.registration_number') }}</label>
                <input id="registration_number" name="registration_number" type="text"
                    value="{{ old('registration_number', $organization->registration_number) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('registration_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="fiscal_number" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.fiscal_number') }}</label>
                <input id="fiscal_number" name="fiscal_number" type="text"
                    value="{{ old('fiscal_number', $organization->fiscal_number) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="{{ __('settings.company.fiscal_number_placeholder') }}">
                @error('fiscal_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="vat_number" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.vat_number') }}</label>
                <input id="vat_number" name="vat_number" type="text"
                    value="{{ old('vat_number', $organization->vat_number) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('vat_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                    <input type="checkbox" name="vat_registered" value="1"
                        @checked(old('vat_registered', $organization->vat_registered))>
                    {{ __('settings.company.vat_registered') }}
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.workspace_url') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.workspace_url_help', ['url' => $workspaceUrl]) }}</p>
        <div class="mt-4">
            <label for="slug" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.url_slug') }}</label>
            <div class="mt-1 flex rounded-lg border border-ziifra-line">
                <span class="inline-flex items-center rounded-l-lg bg-ziifra-cream px-3 text-sm text-ziifra-muted">/o/</span>
                <input id="slug" name="slug" type="text" required
                    value="{{ old('slug', $organization->slug) }}"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="block w-full rounded-r-lg border-0 px-3 py-2 font-mono text-sm focus:ring-ziifra-accent/25">
            </div>
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('settings.company.slug_help') }}</p>
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.address_contact') }}</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="address_line_1" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.address_line_1') }}</label>
                <input id="address_line_1" name="address_line_1" type="text"
                    value="{{ old('address_line_1', $organization->address_line_1) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('address_line_1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address_line_2" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.address_line_2') }}</label>
                <input id="address_line_2" name="address_line_2" type="text"
                    value="{{ old('address_line_2', $organization->address_line_2) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('address_line_2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="city" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.city') }}</label>
                <input id="city" name="city" type="text" value="{{ old('city', $organization->city) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="postal_code" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.postal_code') }}</label>
                <input id="postal_code" name="postal_code" type="text"
                    value="{{ old('postal_code', $organization->postal_code) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="country_code" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.country') }} <span class="text-red-600">*</span></label>
                <select id="country_code" name="country_code" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($countries as $code => $label)
                        <option value="{{ $code }}" @selected(old('country_code', $organization->country_code) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('country_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.phone') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $organization->phone) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2" placeholder="+383 …">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.company_email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $organization->email) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="hr_email" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.hr_email') }}</label>
                <input id="hr_email" name="hr_email" type="email" value="{{ old('hr_email', $organization->hr_email) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="hr@company.com">
                @error('hr_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="reply_to_email" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.reply_to_email') }}</label>
                <input id="reply_to_email" name="reply_to_email" type="email"
                    value="{{ old('reply_to_email', $organization->reply_to_email) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('reply_to_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="website" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.website') }}</label>
                <input id="website" name="website" type="text" value="{{ old('website', $organization->website) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2" placeholder="www.example.com">
                @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.work_schedule') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.work_schedule_help') }}</p>
        <div class="mt-6 space-y-6">
            <div>
                <p class="text-sm font-medium text-ziifra-ink">{{ __('settings.company.working_days') }}</p>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach ($workWeekDays as $day)
                        <label class="flex items-center gap-2 rounded-lg border border-ziifra-line/80 px-3 py-2 text-sm">
                            <input type="checkbox" name="work_week_days[]" value="{{ $day->value }}"
                                @checked(in_array($day->value, $selectedWorkDays, true))>
                            {{ $day->shortLabel() }}
                        </label>
                    @endforeach
                </div>
                @error('work_week_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="fiscal_year_start_month" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.fiscal_year_starts') }}</label>
                    <select id="fiscal_year_start_month" name="fiscal_year_start_month" required
                        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                        @foreach ($fiscalYearMonths as $month)
                            <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $organization->fiscal_year_start_month) === $month)>
                                {{ \Carbon\Carbon::create(null, $month, 1)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_format" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.date_format') }}</label>
                    <select id="date_format" name="date_format" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                        @foreach ($dateFormats as $format => $label)
                            <option value="{{ $format }}" @selected(old('date_format', $organization->date_format) === $format)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="flex items-start gap-2 text-sm text-ziifra-ink">
                    <input type="checkbox" name="observe_kosovo_holidays" value="1" class="mt-1"
                        @checked(old('observe_kosovo_holidays', $organization->observe_kosovo_holidays))>
                    <span>
                        {{ __('settings.company.observe_kosovo_holidays') }}
                        <span class="mt-1 block text-xs text-ziifra-muted">
                            {{ __('settings.company.observe_kosovo_holidays_includes', ['names' => implode(', ', $kosovoHolidayNames)]) }}
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.employment_defaults') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.employment_defaults_help') }}</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="default_employment_type" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.default_employment_type') }}</label>
                <select id="default_employment_type" name="default_employment_type" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($employmentTypes as $type)
                        <option value="{{ $type->value }}"
                            @selected(old('default_employment_type', $organization->default_employment_type ?? 'full_time') === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                @error('default_employment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="probation_days" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.probation_days') }}</label>
                <input id="probation_days" name="probation_days" type="number" min="0" max="365"
                    value="{{ old('probation_days', $organization->probation_days) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('probation_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="employee_id_prefix" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.employee_id_prefix') }}</label>
                <input id="employee_id_prefix" name="employee_id_prefix" type="text"
                    value="{{ old('employee_id_prefix', $organization->employee_id_prefix) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2" placeholder="e.g. ACME">
                @error('employee_id_prefix')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="handbook_url" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.handbook_url') }}</label>
                <input id="handbook_url" name="handbook_url" type="text"
                    value="{{ old('handbook_url', $organization->handbook_url) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('handbook_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.payroll_documents') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.payroll_documents_help') }}</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="signatory_name" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.signatory_name') }}</label>
                <input id="signatory_name" name="signatory_name" type="text"
                    value="{{ old('signatory_name', $organization->signatory_name) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('signatory_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="signatory_title" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.signatory_title') }}</label>
                <input id="signatory_title" name="signatory_title" type="text"
                    value="{{ old('signatory_title', $organization->signatory_title) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="{{ __('settings.company.signatory_title_placeholder') }}">
                @error('signatory_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_name" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.bank_name') }}</label>
                <input id="bank_name" name="bank_name" type="text"
                    value="{{ old('bank_name', $organization->bank_name) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_iban" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.bank_iban') }}</label>
                <input id="bank_iban" name="bank_iban" type="text"
                    value="{{ old('bank_iban', $organization->bank_iban) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 font-mono text-sm">
                @error('bank_iban')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        @if ($hasPayroll)
            @php
                $payslipTpl = $organization->resolvedPayslipTemplate();
            @endphp
            <div class="mt-8 border-t border-ziifra-line/60 pt-6">
                <h3 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.payslip_template') }}</h3>
                <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.payslip_template_help') }}</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="payslip_layout" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.payslip_layout') }}</label>
                        <select id="payslip_layout" name="payslip_template[layout]"
                            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                            <option value="standard" @selected(old('payslip_template.layout', $payslipTpl['layout']) === 'standard')>{{ __('settings.company.payslip_layout_standard') }}</option>
                            <option value="compact" @selected(old('payslip_template.layout', $payslipTpl['layout']) === 'compact')>{{ __('settings.company.payslip_layout_compact') }}</option>
                            <option value="detailed" @selected(old('payslip_template.layout', $payslipTpl['layout']) === 'detailed')>{{ __('settings.company.payslip_layout_detailed') }}</option>
                        </select>
                        @error('payslip_template.layout')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2 space-y-3">
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="hidden" name="payslip_template[show_logo]" value="0">
                            <input type="checkbox" name="payslip_template[show_logo]" value="1"
                                @checked(old('payslip_template.show_logo', $payslipTpl['show_logo']))>
                            {{ __('settings.company.payslip_show_logo') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="hidden" name="payslip_template[show_legal_block]" value="0">
                            <input type="checkbox" name="payslip_template[show_legal_block]" value="1"
                                @checked(old('payslip_template.show_legal_block', $payslipTpl['show_legal_block']))>
                            {{ __('settings.company.payslip_show_legal_block') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="hidden" name="payslip_template[show_employer_pension]" value="0">
                            <input type="checkbox" name="payslip_template[show_employer_pension]" value="1"
                                @checked(old('payslip_template.show_employer_pension', $payslipTpl['show_employer_pension']))>
                            {{ __('settings.company.payslip_show_employer_pension') }}
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="payslip_footer_note" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.payslip_footer_note') }}</label>
                        <textarea id="payslip_footer_note" name="payslip_template[footer_note]" rows="3"
                            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm"
                            placeholder="{{ __('settings.company.payslip_footer_placeholder') }}">{{ old('payslip_template.footer_note', $payslipTpl['footer_note']) }}</textarea>
                        @error('payslip_template.footer_note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        @endif
        @include('app.settings._company_payslip_preview', ['organization' => $organization])
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.regional_settings') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.regional_settings_help') }}</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="timezone" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.timezone') }}</label>
                <select id="timezone" name="timezone" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($timezones as $tz)
                        <option value="{{ $tz }}" @selected(old('timezone', $organization->timezone) === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="currency" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.currency') }}</label>
                <select id="currency" name="currency" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency }}" @selected(old('currency', $organization->currency) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
                @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="locale" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.locale') }}</label>
                <select id="locale" name="locale" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach (app(\App\Services\LocaleConfigurationService::class)->enabledOptions() as $code => $label)
                        <option value="{{ $code }}" @selected(old('locale', $organization->locale) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('locale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.team_access') }}</h2>
        <div class="mt-4">
            <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                <input type="checkbox" name="hr_can_invite" value="1"
                    @checked(old('hr_can_invite', $organization->hr_can_invite))>
                {{ __('settings.company.hr_can_invite') }}
            </label>
        </div>
    </section>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.company.branding') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('settings.company.branding_help') }}</p>
        <div class="mt-6 space-y-4">
            @if ($organization->hasLogo())
                <div class="flex items-center gap-4">
                    <img src="{{ route('settings.company.logo') }}?v={{ $organization->updated_at?->timestamp }}"
                        alt="{{ $organization->name }} logo"
                        class="h-16 w-16 rounded-lg border border-ziifra-line object-contain bg-ziifra-paper p-1">
                    <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                        {{ __('settings.company.remove_logo') }}
                    </label>
                </div>
            @endif
            <div>
                <label for="logo" class="block text-sm font-medium text-ziifra-ink">{{ $organization->hasLogo() ? __('settings.company.replace_logo') : __('settings.company.company_logo') }}</label>
                <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp"
                    class="mt-1 block w-full text-sm text-ziifra-muted file:mr-4 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-4 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
                <p class="mt-1 text-xs text-ziifra-muted">{{ __('settings.company.logo_help') }}</p>
                @error('logo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.primary_color') }}</label>
                    <div class="mt-1 flex gap-2">
                        <input id="primary_color" name="primary_color" type="text"
                            value="{{ old('primary_color', $organization->primary_color ?? '#1e3a5f') }}"
                            class="block w-full rounded-lg border border-ziifra-line px-3 py-2 font-mono text-sm">
                        <input type="color" class="h-10 w-12 cursor-pointer rounded border border-ziifra-line"
                            value="{{ old('primary_color', $organization->primary_color ?? '#1e3a5f') }}"
                            oninput="document.getElementById('primary_color').value = this.value">
                    </div>
                    @error('primary_color')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="accent_color" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.accent_color') }}</label>
                    <div class="mt-1 flex gap-2">
                        <input id="accent_color" name="accent_color" type="text"
                            value="{{ old('accent_color', $organization->accent_color ?? '#c9a227') }}"
                            class="block w-full rounded-lg border border-ziifra-line px-3 py-2 font-mono text-sm">
                        <input type="color" class="h-10 w-12 cursor-pointer rounded border border-ziifra-line"
                            value="{{ old('accent_color', $organization->accent_color ?? '#c9a227') }}"
                            oninput="document.getElementById('accent_color').value = this.value">
                    </div>
                    @error('accent_color')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="brand_tagline" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.tagline') }}</label>
                <input id="brand_tagline" name="brand_tagline" type="text"
                    value="{{ old('brand_tagline', $organization->brand_tagline) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('brand_tagline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-lg bg-ziifra-accent px-6 py-2.5 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
            {{ __('settings.company.save') }}
        </button>
        <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-muted hover:text-ziifra-ink">{{ __('common.cancel') }}</a>
    </div>
</form>
@endsection
