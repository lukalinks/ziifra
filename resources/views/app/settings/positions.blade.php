@extends('layouts.app')

@section('title', __('settings.positions.title'))
@section('header', __('settings.positions.title'))

@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.positions.add') }}</h2>
        <form method="POST" action="{{ route('settings.positions.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="title" class="block text-sm font-medium text-ziifra-ink">{{ __('settings.positions.field_title') }}</label>
                <input id="title" name="title" type="text" required value="{{ old('title') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                {{ __('settings.positions.submit') }}
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.positions.list_title') }}</h2>
        @if ($positions->isEmpty())
            <p class="mt-4 text-sm text-ziifra-muted">{{ __('settings.positions.empty') }}</p>
        @else
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($positions as $position)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <span class="font-medium text-ziifra-ink">{{ $position->title }}</span>
                        <span class="text-ziifra-muted">{{ trans_choice('settings.positions.employees_count', $position->employees_count, ['count' => $position->employees_count]) }}</span>
                        <form method="POST" action="{{ route('settings.positions.destroy', $position) }}"
                            data-confirm="{{ __('settings.positions.confirm_remove') }}"
                            data-confirm-variant="danger"
                            data-confirm-accept="{{ __('common.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">{{ __('common.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
<p class="mt-6">
    <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('settings.back') }}</a>
</p>
@endsection
