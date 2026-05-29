@extends('layouts.app')

@section('title', __('settings.mail.title'))
@section('header', __('settings.mail.title'))

@section('content')
@php
    $statusLabels = [
        'active' => __('settings.mail.status_active'),
        'platform' => __('settings.mail.status_platform'),
        'incomplete' => __('settings.mail.status_incomplete'),
    ];
    $statusClasses = [
        'active' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200',
        'platform' => 'border-ziifra-line/80 bg-ziifra-cream text-ziifra-muted',
        'incomplete' => 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-100',
    ];
@endphp

<p class="mb-6"><a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep">← {{ __('settings.back') }}</a></p>

<div class="mb-6 flex flex-wrap items-center gap-3">
    <span class="rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $statusClasses[$mailStatus] ?? $statusClasses['platform'] }}">
        {{ $statusLabels[$mailStatus] ?? $statusLabels['platform'] }}
    </span>
    @if ($mailStatus === 'active' && ! empty($mailSettings['from_address']))
        <span class="text-sm text-ziifra-muted">{{ $mailSettings['from_address'] }}</span>
    @endif
</div>

<p class="mb-6 text-sm text-ziifra-muted">{{ __('settings.mail.intro') }}</p>

<div data-mail-settings class="max-w-2xl space-y-6">
    <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
            <label class="flex items-center gap-2 text-sm font-medium text-ziifra-ink">
                <input type="checkbox" name="mail_settings[enabled]" value="1" data-mail-enabled @checked($mailSettings['enabled'])>
                {{ __('settings.mail.use_custom_smtp') }}
            </label>
            <p class="text-xs text-ziifra-muted">{{ __('settings.mail.use_custom_smtp_help') }}</p>
        </section>

        <div data-mail-fields class="space-y-6 transition-opacity">
            <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.provider_presets') }}</h2>
                <p class="text-xs text-ziifra-muted">{{ __('settings.mail.provider_presets_help') }}</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-mail-preset data-host="smtp.hostinger.com" data-port="587" data-encryption="tls"
                        class="ziifra-btn-secondary !px-3 !py-1.5 !text-xs">{{ __('settings.mail.preset_hostinger') }}</button>
                    <button type="button" data-mail-preset data-host="smtp.gmail.com" data-port="587" data-encryption="tls"
                        class="ziifra-btn-secondary !px-3 !py-1.5 !text-xs">{{ __('settings.mail.preset_google') }}</button>
                    <button type="button" data-mail-preset data-host="smtp.office365.com" data-port="587" data-encryption="tls"
                        class="ziifra-btn-secondary !px-3 !py-1.5 !text-xs">{{ __('settings.mail.preset_microsoft') }}</button>
                </div>
            </section>

            <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.smtp_server') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="mail_host" class="ziifra-label-field">{{ __('settings.mail.host') }}</label>
                        <input id="mail_host" name="mail_settings[host]" type="text" value="{{ old('mail_settings.host', $mailSettings['host']) }}"
                            class="ziifra-input" placeholder="smtp.example.com" autocomplete="off">
                        @error('mail_settings.host')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="mail_port" class="ziifra-label-field">{{ __('settings.mail.port') }}</label>
                        <input id="mail_port" name="mail_settings[port]" type="number" min="1" max="65535" data-auto-port="1"
                            value="{{ old('mail_settings.port', $mailSettings['port']) }}" class="ziifra-input">
                        @error('mail_settings.port')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="mail_encryption" class="ziifra-label-field">{{ __('settings.mail.encryption') }}</label>
                        <select id="mail_encryption" name="mail_settings[encryption]" class="ziifra-input">
                            @foreach (['tls' => __('settings.mail.encryption_tls'), 'ssl' => __('settings.mail.encryption_ssl'), 'none' => __('settings.mail.encryption_none')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('mail_settings.encryption', $mailSettings['encryption'] ?: 'tls') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('mail_settings.encryption')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="mail_username" class="ziifra-label-field">{{ __('settings.mail.username') }}</label>
                        <input id="mail_username" name="mail_settings[username]" type="text" value="{{ old('mail_settings.username', $mailSettings['username']) }}"
                            class="ziifra-input" autocomplete="username">
                        @error('mail_settings.username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="mail_password" class="ziifra-label-field">{{ __('settings.mail.password') }}</label>
                        <div class="relative">
                            <input id="mail_password" name="mail_settings[password]" type="password" class="ziifra-input pr-10" autocomplete="new-password"
                                placeholder="{{ $mailSettings['has_password'] ? __('settings.mail.password_unchanged') : '' }}">
                            <button type="button" data-password-toggle="mail_password"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-ziifra-muted hover:text-ziifra-ink"
                                aria-label="{{ __('settings.mail.show_password') }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 12 1 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        @error('mail_settings.password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.sender') }}</h2>
                <p class="text-xs text-ziifra-muted">{{ __('settings.mail.sender_help') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="mail_from_address" class="ziifra-label-field">{{ __('settings.mail.from_address') }}</label>
                        <input id="mail_from_address" name="mail_settings[from_address]" type="email"
                            value="{{ old('mail_settings.from_address', $mailSettings['from_address']) }}" class="ziifra-input">
                        @error('mail_settings.from_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="mail_from_name" class="ziifra-label-field">{{ __('settings.mail.from_name') }}</label>
                        <input id="mail_from_name" name="mail_settings[from_name]" type="text"
                            value="{{ old('mail_settings.from_name', $mailSettings['from_name']) }}" class="ziifra-input">
                        @error('mail_settings.from_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-xs text-ziifra-muted">
                    {{ __('settings.mail.reply_to_hint', ['email' => $organization->notificationReplyTo() ?? __('settings.mail.reply_to_empty')]) }}
                    <a href="{{ route('settings.company.edit') }}" class="text-ziifra-accent-deep hover:underline">{{ __('settings.mail.company_settings_link') }}</a>
                </p>
            </section>

            <section class="rounded-xl border border-dashed border-ziifra-line/80 bg-ziifra-cream/50 p-5">
                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('settings.mail.covers_title') }}</h3>
                <ul class="mt-3 grid gap-1.5 text-sm text-ziifra-muted sm:grid-cols-2">
                    @foreach (__('settings.mail.covers_items') as $item)
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 shrink-0 rounded-full bg-ziifra-accent"></span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <button type="submit" class="ziifra-btn-app">{{ __('settings.company.save') }}</button>
    </form>

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.test_title') }}</h2>
        <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.mail.test_help') }}</p>

        @if (! empty($mailSettings['last_tested_at']))
            <p class="mt-3 text-sm {{ ($mailSettings['last_test_ok'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-200' }}">
                {{ ($mailSettings['last_test_ok'] ?? false) ? __('settings.mail.last_test_ok') : __('settings.mail.last_test_failed') }}
                — {{ \Illuminate\Support\Carbon::parse($mailSettings['last_tested_at'])->timezone($organization->timezone ?? config('app.timezone'))->format('d M Y, H:i') }}
            </p>
        @endif

        <form method="POST" action="{{ route('settings.mail.test') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="test_email" class="ziifra-label-field">{{ __('settings.mail.test_recipient') }}</label>
                <input id="test_email" name="test_email" type="email" value="{{ old('test_email', auth()->user()->email) }}" class="ziifra-input" required
                    @disabled($mailStatus !== 'active')>
                @error('test_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ziifra-btn-secondary shrink-0" @disabled($mailStatus !== 'active')>{{ __('settings.mail.send_test') }}</button>
        </form>
        @if ($mailStatus !== 'active')
            <p class="mt-2 text-xs text-ziifra-muted">{{ __('settings.mail.test_requires_active') }}</p>
        @endif
    </section>
</div>
@endsection
