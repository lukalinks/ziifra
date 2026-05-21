@extends('layouts.app')

@section('title', __('documents.templates.settings.title'))
@section('header', __('documents.templates.settings.title'))

@section('content')
<p class="mb-6">
    <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">← {{ __('documents.templates.settings.back') }}</a>
</p>

<p class="text-sm text-ziifra-muted">{{ __('documents.templates.settings.subtitle') }}</p>

<div class="mt-8 grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.templates.settings.add') }}</h2>
        <form method="POST" action="{{ route('settings.contract-templates.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.name') }}</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="{{ __('documents.templates.settings.name_placeholder') }}">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.description') }}</label>
                <input id="description" name="description" type="text" value="{{ old('description') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="{{ __('documents.templates.settings.description_placeholder') }}">
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="block text-sm font-medium text-ziifra-ink">{{ __('documents.templates.settings.body') }}</label>
                <textarea id="body" name="body" rows="12" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 font-mono text-xs leading-relaxed">{{ old('body', __('documents.templates.settings.body_starter')) }}</textarea>
                @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="rounded-lg bg-ziifra-cream/60 p-3">
                <p class="text-xs font-medium text-ziifra-ink">{{ __('documents.templates.settings.placeholders_title') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ implode(', ', $placeholders) }}</p>
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-ziifra-ink hover:bg-ziifra-accent-glow">
                {{ __('documents.templates.settings.add_button') }}
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.templates.settings.list') }}</h2>
        @if ($contractTemplates->isEmpty())
            <p class="mt-4 text-sm text-ziifra-muted">{{ __('documents.templates.settings.empty') }}</p>
        @else
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($contractTemplates as $contractTemplate)
                    <li class="flex items-start justify-between gap-4 py-3 text-sm">
                        <div class="min-w-0">
                            <span class="font-medium text-ziifra-ink">{{ $contractTemplate->name }}</span>
                            @if ($contractTemplate->description)
                                <span class="mt-0.5 block text-ziifra-muted">{{ $contractTemplate->description }}</span>
                            @endif
                            <span class="mt-1 block text-xs text-ziifra-muted">
                                @if ($contractTemplate->is_system)
                                    {{ __('documents.templates.settings.system_badge') }}
                                @else
                                    {{ __('documents.templates.settings.custom_badge') }}
                                @endif
                                · {{ $contractTemplate->is_active ? __('documents.templates.settings.active') : __('documents.templates.settings.inactive') }}
                            </span>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <a href="{{ route('settings.contract-templates.edit', $contractTemplate) }}"
                                class="text-ziifra-accent-deep hover:underline">
                                {{ __('documents.templates.settings.edit') }}
                            </a>
                            @unless ($contractTemplate->is_system)
                                <form method="POST" action="{{ route('settings.contract-templates.destroy', $contractTemplate) }}"
                                    data-confirm="{{ __('documents.templates.settings.confirm_delete') }}"
                                    data-confirm-variant="danger"
                                    data-confirm-accept="{{ __('common.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700">{{ __('common.remove') }}</button>
                                </form>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
