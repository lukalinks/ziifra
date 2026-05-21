@extends('layouts.app')

@section('title', __('time.add_entry'))
@section('header', __('time.add_entry'))

@section('content')
<form method="POST" action="{{ route('time.entries.store') }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    <input type="hidden" name="week" value="{{ $week }}">

    <p class="mb-6 text-sm text-ziifra-muted">{{ __('time.manual_entry_hint') }}</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="employee_id" class="ziifra-label-field">{{ __('time.employee') }}</label>
            <select id="employee_id" name="employee_id" required class="ziifra-input">
                <option value="" disabled @selected(! old('employee_id', $employeeId))>{{ __('time.select_employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $employeeId) == $employee->id)>{{ $employee->fullName() }}</option>
                @endforeach
            </select>
            @error('employee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="clock_in" class="ziifra-label-field">{{ __('time.clock_in') }}</label>
            <input type="datetime-local" id="clock_in" name="clock_in" value="{{ old('clock_in') }}" required class="ziifra-input">
            @error('clock_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="clock_out" class="ziifra-label-field">{{ __('time.clock_out') }}</label>
            <input type="datetime-local" id="clock_out" name="clock_out" value="{{ old('clock_out') }}" class="ziifra-input">
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('time.clock_out_optional') }}</p>
            @error('clock_out')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="break_minutes" class="ziifra-label-field">{{ __('time.break') }}</label>
            <input type="number" id="break_minutes" name="break_minutes" min="0" max="480" value="{{ old('break_minutes', 0) }}" class="ziifra-input">
            @error('break_minutes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="notes" class="ziifra-label-field">{{ __('time.notes') }}</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000" class="ziifra-input">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('time.save_entry') }}</button>
        <a href="{{ route('time.index', array_filter(['week' => $week, 'employee_id' => $employeeId])) }}" class="ziifra-btn-app-outline">{{ __('time.cancel') }}</a>
    </div>
</form>
@endsection
