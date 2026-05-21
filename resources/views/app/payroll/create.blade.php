@extends('layouts.app')

@section('title', __('payroll.create_run'))
@section('header', __('payroll.create_run'))

@section('content')
<div class="max-w-lg">
    <p class="mb-6 text-sm text-ziifra-muted">{{ __('payroll.rules_notice') }}</p>

    <form method="POST" action="{{ route('payroll.store') }}" class="space-y-4 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        @csrf
        <div>
            <label for="year" class="block text-sm font-medium text-ziifra-ink">{{ __('payroll.year') }}</label>
            <input id="year" name="year" type="number" required min="2020" max="2100"
                value="{{ old('year', $defaultYear) }}"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error('year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="month" class="block text-sm font-medium text-ziifra-ink">{{ __('payroll.month') }}</label>
            <select id="month" name="month" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected((int) old('month', $defaultMonth) === $m)>
                        {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                    </option>
                @endfor
            </select>
            @error('month')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="ziifra-btn-primary">{{ __('payroll.create_run') }}</button>
            <a href="{{ route('payroll.index') }}" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">Cancel</a>
        </div>
    </form>
</div>
@endsection
