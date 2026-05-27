@extends('layouts.app')

@section('title', __('time.edit_entry'))
@section('header', __('time.edit_entry'))

@section('content')
<form method="POST" action="{{ route('time.entries.update', $entry) }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @method('PUT')
    <input type="hidden" name="week" value="{{ $week }}">

    <div class="mb-6 rounded-lg border border-ziifra-line/60 bg-ziifra-cream/30 px-4 py-3 text-sm">
        <p class="font-medium text-ziifra-ink">{{ $entry->employee->fullName() }}</p>
        <p class="mt-1 text-xs text-ziifra-muted">
            {{ __('time.recorded_by', ['name' => $entry->recordedBy?->name ?? '—']) }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="clock_in" class="ziifra-label-field">{{ __('time.clock_in') }}</label>
            <input type="datetime-local" id="clock_in" name="clock_in"
                value="{{ old('clock_in', $entry->clock_in->format('Y-m-d\TH:i')) }}" required class="ziifra-input">
            @error('clock_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="clock_out" class="ziifra-label-field">{{ __('time.clock_out') }}</label>
            <input type="datetime-local" id="clock_out" name="clock_out"
                value="{{ old('clock_out', $entry->clock_out?->format('Y-m-d\TH:i')) }}" class="ziifra-input">
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('time.clock_out_optional') }}</p>
            @error('clock_out')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="break_minutes" class="ziifra-label-field">{{ __('time.break') }}</label>
            <input type="number" id="break_minutes" name="break_minutes" min="0" max="480"
                value="{{ old('break_minutes', $entry->break_minutes) }}" class="ziifra-input">
            @error('break_minutes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="notes" class="ziifra-label-field">{{ __('time.notes') }}</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000" class="ziifra-input">{{ old('notes', $entry->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('time.save_entry') }}</button>
        <a href="{{ route('time.index', array_filter(['week' => $week, 'employee' => $entry->employee->employee_code])) }}" class="ziifra-btn-app-outline">{{ __('time.cancel') }}</a>
    </div>
</form>

<form method="POST" action="{{ route('time.entries.destroy', $entry) }}" class="mt-4 max-w-3xl"
    data-confirm="{{ __('time.delete_confirm') }}"
    data-confirm-variant="danger"
    data-confirm-accept="{{ __('time.delete_entry') }}">
    @csrf
    @method('DELETE')
    <input type="hidden" name="week" value="{{ $week }}">
    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ __('time.delete_entry') }}</button>
</form>
@endsection
