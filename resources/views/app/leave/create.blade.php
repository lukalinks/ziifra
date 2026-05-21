@extends('layouts.app')

@section('title', $isSelfService ? __('leave.create.title_self') : __('leave.create.title_hr'))
@section('header', $isSelfService ? __('leave.create.title_self') : __('leave.create.title_hr'))

@section('content')
<div class="mx-auto max-w-xl">
    @if ($isSelfService && $linkedEmployee)
        <p class="mb-4 text-sm text-ziifra-muted">
            {{ __('leave.create.submitting_for', ['name' => $linkedEmployee->fullName()]) }}
        </p>
    @endif

    <form method="POST" action="{{ route('leave.store') }}" class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
        @csrf

        @if (! $isSelfService)
            <div>
                <label for="employee_id" class="block text-sm font-medium text-ziifra-ink">{{ __('leave.create.employee') }} <span class="text-red-600">*</span></label>
                <select id="employee_id" name="employee_id" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    <option value="">{{ __('leave.create.select') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endif

        <div>
            <label for="leave_type_id" class="block text-sm font-medium text-ziifra-ink">{{ __('leave.create.leave_type') }} <span class="text-red-600">*</span></label>
            <select id="leave_type_id" name="leave_type_id" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                <option value="">{{ __('leave.create.select') }}</option>
                @foreach ($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                        {{ __('leave.create.leave_type_option', ['name' => $type->name, 'days' => number_format($type->default_days_per_year, 0)]) }}
                    </option>
                @endforeach
            </select>
            @error('leave_type_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @if ($leaveTypes->isEmpty())
                <p class="mt-2 text-sm text-amber-700">
                    @if ($isSelfService)
                        {{ __('leave.create.no_leave_types_employee') }}
                    @else
                        <a href="{{ route('settings.leave-types.index') }}" class="underline">{{ __('leave.create.no_leave_types_hr') }}</a>
                    @endif
                </p>
            @endif
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="block text-sm font-medium text-ziifra-ink">{{ __('leave.create.start_date') }} <span class="text-red-600">*</span></label>
                <input id="start_date" name="start_date" type="date" required value="{{ old('start_date') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-ziifra-ink">{{ __('leave.create.end_date') }} <span class="text-red-600">*</span></label>
                <input id="end_date" name="end_date" type="date" required value="{{ old('end_date') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="text-xs text-ziifra-muted">{{ __('leave.create.working_days_hint') }}</p>
        <div>
            <label for="notes" class="block text-sm font-medium text-ziifra-ink">{{ __('leave.create.notes') }}</label>
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                {{ __('leave.create.submit') }}
            </button>
            <a href="{{ route('leave.index') }}" data-page-nav class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
                {{ __('common.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
