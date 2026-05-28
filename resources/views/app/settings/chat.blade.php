@extends('layouts.app')

@section('title', __('settings.chat.title'))
@section('header', __('settings.chat.title'))

@section('content')
<p class="mb-6"><a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep">← {{ __('settings.back') }}</a></p>

<form method="POST" action="{{ route('settings.chat.update') }}" class="max-w-xl space-y-6">
    @csrf
    @method('PUT')
    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
            <input type="checkbox" name="chat_settings[enabled]" value="1" @checked($chatSettings['enabled'] ?? true)>
            {{ __('settings.chat.enabled') }}
        </label>
        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
            <input type="checkbox" name="chat_settings[employees_can_write]" value="1" @checked($chatSettings['employees_can_write'] ?? true)>
            {{ __('settings.chat.employees_can_write') }}
        </label>
        <p class="text-xs text-ziifra-muted">{{ __('settings.chat.employees_can_write_help') }}</p>
        <label class="flex items-center gap-2 text-sm text-ziifra-ink">
            <input type="checkbox" name="chat_settings[private_chat_enabled]" value="1" @checked($chatSettings['private_chat_enabled'] ?? true)>
            {{ __('settings.chat.private_chat_enabled') }}
        </label>
        <p class="text-xs text-ziifra-muted">{{ __('settings.chat.private_chat_enabled_help') }}</p>
    </section>
    <button type="submit" class="ziifra-btn-app">{{ __('settings.company.save') }}</button>
</form>
@endsection
