@extends('admin.layout')

@section('title', __('admin.languages.heading'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.languages.heading') }}</h1>
    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ __('admin.languages.subtitle') }}</p>
</div>

<form method="POST" action="{{ route('admin.languages.update') }}" class="space-y-8">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.languages.enabled_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('admin.languages.enabled_help') }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            @foreach ($definitions as $code => $definition)
                <label class="flex cursor-pointer flex-col rounded-lg border border-slate-200 p-4 hover:border-slate-400 has-[:checked]:border-slate-900 has-[:checked]:ring-1 has-[:checked]:ring-slate-900">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="enabled[]" value="{{ $code }}"
                            @checked(in_array($code, old('enabled', $enabled), true))
                            class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $definition['native'] }}</p>
                            <p class="text-sm text-slate-500">{{ $definition['label'] }} · {{ strtoupper($code) }}</p>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
        @error('enabled')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.languages.default_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('admin.languages.default_help') }}</p>

        <div class="mt-4 flex flex-wrap gap-4">
            @foreach ($definitions as $code => $definition)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="default" value="{{ $code }}"
                        @checked(old('default', $default) === $code)
                        class="border-slate-300 text-slate-900 focus:ring-slate-500">
                    <span>{{ $definition['native'] }}</span>
                </label>
            @endforeach
        </div>
        @error('default')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('admin.languages.save') }}
        </button>
    </div>
</form>
@endsection
