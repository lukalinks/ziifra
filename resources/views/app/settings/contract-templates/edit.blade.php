@extends('layouts.app')

@section('title', __('documents.templates.settings.edit_title', ['name' => $template->name]))
@section('header', __('documents.templates.settings.edit_title', ['name' => $template->name]))

@section('content')
<p class="mb-6">
    <a href="{{ route('settings.contract-templates.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">← {{ __('documents.templates.settings.back_to_list') }}</a>
</p>

<form method="POST" action="{{ route('settings.contract-templates.update', $template) }}" class="space-y-6 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @method('PUT')

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.name') }}</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $template->name) }}"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.description') }}</label>
            <input id="description" name="description" type="text" value="{{ old('description', $template->description) }}"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                <input type="checkbox" name="is_active" value="1" class="rounded border-ziifra-line" @checked(old('is_active', $template->is_active))>
                {{ __('documents.templates.settings.is_active') }}
            </label>
        </div>
    </div>

    <div>
        <label for="body" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.body') }}</label>
        <p class="mt-1 text-xs text-ziifra-muted">{{ __('documents.templates.settings.body_hint') }}</p>
        <textarea id="body" name="body" rows="18" required
            class="mt-2 block w-full rounded-lg border border-ziifra-line px-3 py-2 font-mono text-xs leading-relaxed">{{ old('body', $template->body) }}</textarea>
        @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="rounded-lg bg-ziifra-cream/60 p-3">
        <p class="text-xs font-medium text-ziifra-ink">{{ __('documents.templates.settings.placeholders_title') }}</p>
        <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ implode(', ', $placeholders) }}</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-ziifra-ink hover:bg-ziifra-accent-glow">
            {{ __('documents.templates.settings.save') }}
        </button>
        <a href="{{ route('settings.contract-templates.index') }}" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            {{ __('common.cancel') }}
        </a>
    </div>
</form>
@endsection
