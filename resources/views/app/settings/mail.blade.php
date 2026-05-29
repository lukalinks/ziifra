@extends('layouts.app')

@section('title', __('settings.mail.title'))
@section('header', __('settings.mail.title'))

@section('content')
<p class="mb-6"><a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep">← {{ __('settings.back') }}</a></p>

<p class="mb-6 text-sm text-ziifra-muted">{{ __('settings.mail.intro') }}</p>

@if ($usesPlatformMail)
    <p class="mb-6 rounded-lg border border-ziifra-line/80 bg-ziifra-cream px-4 py-3 text-sm text-ziifra-muted">
        {{ __('settings.mail.platform_default') }}
    </p>
@endif

<form method="POST" action="{{ route('settings.mail.update') }}" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
        <label class="flex items-center gap-2 text-sm font-medium text-ziifra-ink">
            <input type="checkbox" name="mail_settings[enabled]" value="1" @checked($mailSettings['enabled'])>
            {{ __('settings.mail.use_custom_smtp') }}
        </label>
        <p class="text-xs text-ziifra-muted">{{ __('settings.mail.use_custom_smtp_help') }}</p>
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
                <input id="mail_port" name="mail_settings[port]" type="number" min="1" max="65535"
                    value="{{ old('mail_settings.port', $mailSettings['port']) }}" class="ziifra-input">
                @error('mail_settings.port')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="mail_encryption" class="ziifra-label-field">{{ __('settings.mail.encryption') }}</label>
                <select id="mail_encryption" name="mail_settings[encryption]" class="ziifra-input">
                    @foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => __('settings.mail.encryption_none')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('mail_settings.encryption', $mailSettings['encryption'] ?: 'tls') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('mail_settings.encryption')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="mail_username" class="ziifra-label-field">{{ __('settings.mail.username') }}</label>
                <input id="mail_username" name="mail_settings[username]" type="text" value="{{ old('mail_settings.username', $mailSettings['username']) }}"
                    class="ziifra-input" autocomplete="off">
                @error('mail_settings.username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="mail_password" class="ziifra-label-field">{{ __('settings.mail.password') }}</label>
                <input id="mail_password" name="mail_settings[password]" type="password" class="ziifra-input" autocomplete="new-password"
                    placeholder="{{ $mailSettings['has_password'] ? __('settings.mail.password_unchanged') : '' }}">
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
        <p class="text-xs text-ziifra-muted">{{ __('settings.mail.reply_to_hint', ['email' => $organization->notificationReplyTo() ?? __('settings.mail.reply_to_empty')]) }}</p>
    </section>

    <button type="submit" class="ziifra-btn-app">{{ __('settings.company.save') }}</button>
</form>

<section class="mt-10 max-w-2xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.mail.test_title') }}</h2>
    <p class="mt-2 text-sm text-ziifra-muted">{{ __('settings.mail.test_help') }}</p>
    <form method="POST" action="{{ route('settings.mail.test') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label for="test_email" class="ziifra-label-field">{{ __('settings.mail.test_recipient') }}</label>
            <input id="test_email" name="test_email" type="email" value="{{ old('test_email', auth()->user()->email) }}" class="ziifra-input" required>
            @error('test_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="ziifra-btn-secondary shrink-0">{{ __('settings.mail.send_test') }}</button>
    </form>
</section>
@endsection
