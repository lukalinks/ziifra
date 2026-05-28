@extends('layouts.app')

@section('title', __('settings.company.title'))
@section('header', __('settings.company.title'))

@section('content')
@php
    $selectedWorkDays = old('work_week_days', $organization->workWeekDayValues());

    $completionFields = [
        $organization->name,
        $organization->legal_name,
        $organization->legal_form?->value,
        $organization->registration_number,
        $organization->address_line_1,
        $organization->city,
        $organization->country_code,
        $organization->email,
        $organization->phone,
        $organization->currency,
    ];
    $filled = collect($completionFields)->filter(fn ($v) => filled($v))->count();
    $completion = (int) round(($filled / count($completionFields)) * 100);

    $navItems = [
        ['id' => 'identity', 'label' => __('settings.company.identity'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['id' => 'address', 'label' => __('settings.company.address_contact'), 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['id' => 'schedule', 'label' => __('settings.company.work_schedule'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['id' => 'employment', 'label' => __('settings.company.employment_defaults'), 'icon' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z'],
        ['id' => 'regional', 'label' => __('settings.company.regional_settings'), 'icon' => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a8.949 8.949 0 004.951-1.488A3.987 3.987 0 0013 16.5h-2a4 4 0 00-3.951 3.512A8.949 8.949 0 0012 21z M3.6 9h16.8M3.6 15h16.8'],
        ['id' => 'branding', 'label' => __('settings.company.branding'), 'icon' => 'M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42'],
    ];
@endphp

<form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="mx-auto max-w-5xl pb-4">
    @csrf
    @method('PUT')

    <a href="{{ route('settings.index') }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('settings.back') }}
    </a>

    <section class="ziifra-settings-hero mt-4">
        <div class="relative z-[1] flex flex-wrap items-start justify-between gap-6">
            <div class="flex items-start gap-4">
                <span class="ziifra-settings-hero-badge" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ $organization->name }}</h1>
                    <p class="mt-1 max-w-md text-sm text-ziifra-muted">{{ __('settings.company.complete_profile_hint') }}</p>
                </div>
            </div>

            <div class="w-full max-w-xs sm:w-56">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-ziifra-ink">{{ __('settings.company.profile_completion') }}</span>
                    <span class="font-semibold tabular-nums text-ziifra-accent-deep">{{ $completion }}%</span>
                </div>
                <div class="ziifra-settings-progress-track">
                    <div class="ziifra-settings-progress-fill" style="width: {{ $completion }}%"></div>
                </div>
                @if ($completion < 100)
                    <p class="mt-2 text-xs text-ziifra-muted">{{ __('settings.company.complete_profile') }}</p>
                @endif
            </div>
        </div>
    </section>

    <div class="ziifra-settings-layout">
        <nav class="ziifra-settings-nav" aria-label="{{ __('settings.company.title') }}">
            @foreach ($navItems as $item)
                <a href="#{{ $item['id'] }}" class="ziifra-settings-nav-link">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="min-w-0">
            <section id="identity" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[0]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.identity') }}</h2>
                        <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('settings.company.identity_help') }}</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="ziifra-label-field">{{ __('settings.company.display_name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $organization->name) }}" class="ziifra-input">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="legal_name" class="ziifra-label-field">{{ __('settings.company.legal_name') }}</label>
                        <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name', $organization->legal_name) }}" class="ziifra-input" placeholder="{{ __('settings.company.legal_name_placeholder') }}">
                        @error('legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="legal_form" class="ziifra-label-field">{{ __('settings.company.legal_form') }}</label>
                        <select id="legal_form" name="legal_form" class="ziifra-input">
                            <option value="">{{ __('settings.company.select') }}</option>
                            @foreach ($legalForms as $form)
                                <option value="{{ $form->value }}" @selected(old('legal_form', $organization->legal_form?->value) === $form->value)>{{ $form->label() }}</option>
                            @endforeach
                        </select>
                        @error('legal_form')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="registration_number" class="ziifra-label-field">{{ __('settings.company.registration_number') }}</label>
                        <input id="registration_number" name="registration_number" type="text" value="{{ old('registration_number', $organization->registration_number) }}" class="ziifra-input">
                        @error('registration_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="fiscal_number" class="ziifra-label-field">{{ __('settings.company.fiscal_number') }}</label>
                        <input id="fiscal_number" name="fiscal_number" type="text" value="{{ old('fiscal_number', $organization->fiscal_number) }}" class="ziifra-input" placeholder="{{ __('settings.company.fiscal_number_placeholder') }}">
                        @error('fiscal_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="vat_number" class="ziifra-label-field">{{ __('settings.company.vat_number') }}</label>
                        <input id="vat_number" name="vat_number" type="text" value="{{ old('vat_number', $organization->vat_number) }}" class="ziifra-input">
                        @error('vat_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="checkbox" name="vat_registered" value="1" @checked(old('vat_registered', $organization->vat_registered))>
                            {{ __('settings.company.vat_registered') }}
                        </label>
                    </div>
                    <div class="sm:col-span-2 border-t border-ziifra-line/60 pt-4">
                        <label for="slug" class="ziifra-label-field">{{ __('settings.company.url_slug') }}</label>
                        <div class="mt-1 flex rounded-lg border border-ziifra-line">
                            <span class="inline-flex items-center rounded-l-lg bg-ziifra-cream px-3 text-sm text-ziifra-muted">/o/</span>
                            <input id="slug" name="slug" type="text" value="{{ old('slug', $organization->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" class="block w-full rounded-r-lg border-0 bg-ziifra-surface px-3 py-2 font-mono text-sm text-ziifra-ink focus:ring-2 focus:ring-ziifra-accent/25">
                        </div>
                        <p class="mt-1 text-xs text-ziifra-muted">{{ __('settings.company.workspace_url_help', ['url' => $workspaceUrl]) }}</p>
                        @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section id="address" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[1]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.address_contact') }}</h2>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="address_line_1" class="ziifra-label-field">{{ __('settings.company.address_line_1') }}</label>
                        <input id="address_line_1" name="address_line_1" type="text" value="{{ old('address_line_1', $organization->address_line_1) }}" class="ziifra-input">
                        @error('address_line_1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address_line_2" class="ziifra-label-field">{{ __('settings.company.address_line_2') }}</label>
                        <input id="address_line_2" name="address_line_2" type="text" value="{{ old('address_line_2', $organization->address_line_2) }}" class="ziifra-input">
                        @error('address_line_2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="city" class="ziifra-label-field">{{ __('settings.company.city') }}</label>
                        <input id="city" name="city" type="text" value="{{ old('city', $organization->city) }}" class="ziifra-input">
                        @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="postal_code" class="ziifra-label-field">{{ __('settings.company.postal_code') }}</label>
                        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $organization->postal_code) }}" class="ziifra-input">
                        @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="country_code" class="ziifra-label-field">{{ __('settings.company.country') }}</label>
                        <select id="country_code" name="country_code" class="ziifra-input">
                            @foreach ($countries as $code => $label)
                                <option value="{{ $code }}" @selected(old('country_code', $organization->country_code) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('country_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="ziifra-label-field">{{ __('settings.company.phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $organization->phone) }}" class="ziifra-input" placeholder="+383 …">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="ziifra-label-field">{{ __('settings.company.company_email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $organization->email) }}" class="ziifra-input">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="hr_email" class="ziifra-label-field">{{ __('settings.company.hr_email') }}</label>
                        <input id="hr_email" name="hr_email" type="email" value="{{ old('hr_email', $organization->hr_email) }}" class="ziifra-input" placeholder="hr@company.com">
                        @error('hr_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reply_to_email" class="ziifra-label-field">{{ __('settings.company.reply_to_email') }}</label>
                        <input id="reply_to_email" name="reply_to_email" type="email" value="{{ old('reply_to_email', $organization->reply_to_email) }}" class="ziifra-input">
                        @error('reply_to_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="website" class="ziifra-label-field">{{ __('settings.company.website') }}</label>
                        <input id="website" name="website" type="text" value="{{ old('website', $organization->website) }}" class="ziifra-input" placeholder="www.example.com">
                        @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section id="schedule" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[2]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.work_schedule') }}</h2>
                        <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('settings.company.work_schedule_help') }}</p>
                    </div>
                </div>
                <div class="mt-5 space-y-6">
                    <div>
                        <p class="text-sm font-medium text-ziifra-ink">{{ __('settings.company.working_days') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2.5">
                            @foreach ($workWeekDays as $day)
                                <label class="flex items-center gap-2 rounded-lg border border-ziifra-line/80 px-3 py-2 text-sm">
                                    <input type="checkbox" name="work_week_days[]" value="{{ $day->value }}" @checked(in_array($day->value, $selectedWorkDays, true))>
                                    {{ $day->shortLabel() }}
                                </label>
                            @endforeach
                        </div>
                        @error('work_week_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="fiscal_year_start_month" class="ziifra-label-field">{{ __('settings.company.fiscal_year_starts') }}</label>
                            <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="ziifra-input">
                                @foreach ($fiscalYearMonths as $month)
                                    <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $organization->fiscal_year_start_month) === $month)>{{ \Carbon\Carbon::create(null, $month, 1)->format('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_format" class="ziifra-label-field">{{ __('settings.company.date_format') }}</label>
                            <select id="date_format" name="date_format" class="ziifra-input">
                                @foreach ($dateFormats as $format => $label)
                                    <option value="{{ $format }}" @selected(old('date_format', $organization->date_format) === $format)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="flex items-start gap-2 text-sm text-ziifra-ink">
                            <input type="checkbox" name="observe_kosovo_holidays" value="1" class="mt-1" @checked(old('observe_kosovo_holidays', $organization->observe_kosovo_holidays))>
                            <span>
                                {{ __('settings.company.observe_kosovo_holidays') }}
                                <span class="mt-1 block text-xs text-ziifra-muted">{{ __('settings.company.observe_kosovo_holidays_includes', ['names' => implode(', ', $kosovoHolidayNames)]) }}</span>
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            <section id="employment" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[3]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.employment_defaults') }}</h2>
                        <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('settings.company.employment_defaults_help') }}</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="default_employment_type" class="ziifra-label-field">{{ __('settings.company.default_employment_type') }}</label>
                        <select id="default_employment_type" name="default_employment_type" class="ziifra-input">
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('default_employment_type', $organization->default_employment_type ?? 'full_time') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('default_employment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="probation_days" class="ziifra-label-field">{{ __('settings.company.probation_days') }}</label>
                        <input id="probation_days" name="probation_days" type="number" min="0" max="365" value="{{ old('probation_days', $organization->probation_days) }}" class="ziifra-input">
                        @error('probation_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="employee_id_prefix" class="ziifra-label-field">{{ __('settings.company.employee_id_prefix') }}</label>
                        <input id="employee_id_prefix" name="employee_id_prefix" type="text" value="{{ old('employee_id_prefix', $organization->employee_id_prefix) }}" class="ziifra-input" placeholder="e.g. ACME">
                        @error('employee_id_prefix')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="handbook_url" class="ziifra-label-field">{{ __('settings.company.handbook_url') }}</label>
                        <input id="handbook_url" name="handbook_url" type="text" value="{{ old('handbook_url', $organization->handbook_url) }}" class="ziifra-input">
                        @error('handbook_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2 border-t border-ziifra-line/60 pt-4">
                        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                            <input type="checkbox" name="hr_can_invite" value="1" @checked(old('hr_can_invite', $organization->hr_can_invite))>
                            {{ __('settings.company.hr_can_invite') }}
                        </label>
                    </div>
                </div>
            </section>

            <section id="regional" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[4]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.regional_settings') }}</h2>
                        <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('settings.company.regional_settings_help') }}</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="timezone" class="ziifra-label-field">{{ __('settings.company.timezone') }}</label>
                        <select id="timezone" name="timezone" class="ziifra-input">
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $organization->timezone) === $tz)>{{ $tz === 'Europe/Zurich' ? 'Europe/Bern' : $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="currency" class="ziifra-label-field">{{ __('settings.company.currency') }}</label>
                        <select id="currency" name="currency" class="ziifra-input">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', $organization->currency) === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="locale" class="ziifra-label-field">{{ __('settings.company.locale') }}</label>
                        <select id="locale" name="locale" class="ziifra-input">
                            @foreach (app(\App\Services\LocaleConfigurationService::class)->enabledOptions() as $code => $label)
                                <option value="{{ $code }}" @selected(old('locale', $organization->locale) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('locale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section id="branding" class="ziifra-settings-card">
                <div class="ziifra-settings-card-head">
                    <span class="ziifra-settings-card-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navItems[5]['icon'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ziifra-ink">{{ __('settings.company.branding') }}</h2>
                        <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('settings.company.branding_help') }}</p>
                    </div>
                </div>
                <div class="mt-5 space-y-5">
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-dashed border-ziifra-line bg-ziifra-surface/50 p-4">
                        @if ($organization->hasLogo())
                            <img src="{{ route('settings.company.logo') }}?v={{ $organization->updated_at?->timestamp }}" alt="{{ $organization->name }} logo" class="h-16 w-16 rounded-lg border border-ziifra-line object-contain bg-ziifra-paper p-1">
                        @else
                            <span class="flex h-16 w-16 items-center justify-center rounded-lg border border-ziifra-line bg-ziifra-paper text-ziifra-muted" aria-hidden="true">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            </span>
                        @endif
                        <div class="min-w-[12rem] flex-1">
                            <label for="logo" class="ziifra-label-field">{{ $organization->hasLogo() ? __('settings.company.replace_logo') : __('settings.company.company_logo') }}</label>
                            <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-ziifra-muted file:mr-4 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-4 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
                            <p class="mt-1 text-xs text-ziifra-muted">{{ __('settings.company.logo_help') }}</p>
                            @error('logo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            @if ($organization->hasLogo())
                                <label class="mt-2 flex items-center gap-2 text-sm text-ziifra-ink">
                                    <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                    {{ __('settings.company.remove_logo') }}
                                </label>
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="primary_color" class="ziifra-label-field">{{ __('settings.company.primary_color') }}</label>
                            <div class="mt-1 flex gap-2">
                                <input id="primary_color" name="primary_color" type="text" value="{{ old('primary_color', $organization->primary_color ?? '#1e3a5f') }}" class="block w-full rounded-lg border border-ziifra-line bg-ziifra-surface px-3 py-2 font-mono text-sm text-ziifra-ink">
                                <input type="color" class="h-10 w-12 cursor-pointer rounded border border-ziifra-line" value="{{ old('primary_color', $organization->primary_color ?? '#1e3a5f') }}" oninput="document.getElementById('primary_color').value = this.value">
                            </div>
                            @error('primary_color')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="accent_color" class="ziifra-label-field">{{ __('settings.company.accent_color') }}</label>
                            <div class="mt-1 flex gap-2">
                                <input id="accent_color" name="accent_color" type="text" value="{{ old('accent_color', $organization->accent_color ?? '#c9a227') }}" class="block w-full rounded-lg border border-ziifra-line bg-ziifra-surface px-3 py-2 font-mono text-sm text-ziifra-ink">
                                <input type="color" class="h-10 w-12 cursor-pointer rounded border border-ziifra-line" value="{{ old('accent_color', $organization->accent_color ?? '#c9a227') }}" oninput="document.getElementById('accent_color').value = this.value">
                            </div>
                            @error('accent_color')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="brand_tagline" class="ziifra-label-field">{{ __('settings.company.tagline') }}</label>
                        <input id="brand_tagline" name="brand_tagline" type="text" value="{{ old('brand_tagline', $organization->brand_tagline) }}" class="ziifra-input">
                        @error('brand_tagline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <div class="ziifra-settings-savebar">
                <p class="text-sm text-ziifra-muted">{{ __('settings.company.save_hint') }}</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('settings.index') }}" class="text-sm font-medium text-ziifra-muted hover:text-ziifra-ink">{{ __('common.cancel') }}</a>
                    <button type="submit" class="ziifra-btn-app !text-sm">{{ __('settings.company.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
