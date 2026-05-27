@extends('layouts.app')

@section('title', __('payroll.create_run'))
@section('header', __('payroll.create_run'))

@section('content')
@php
    $selectedEmployeeIds = array_map('intval', old('employee_ids', []));
    $generationMode = old('generation_mode', 'all');
@endphp

<div class="ziifra-dashboard-page ziifra-payroll-create mx-auto max-w-2xl">
    <a href="{{ route('payroll.index') }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('payroll.back_to_list') }}
    </a>

    <header class="ziifra-payroll-create-head">
        <h1 class="text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ __('payroll.create_run') }}</h1>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('payroll.create_subtitle') }}</p>
    </header>

    <div class="ziifra-payroll-rules-notice" role="note">
        {{ __('payroll.rules_notice') }}
    </div>

    <form method="POST" action="{{ route('payroll.store') }}" class="ziifra-payroll-create-form" data-payroll-create-form>
        @csrf

        <section class="ziifra-payroll-create-section">
            <header class="ziifra-payroll-create-section-head">
                <h2 class="ziifra-payroll-create-section-title">{{ __('payroll.section_period') }}</h2>
            </header>
            <div class="ziifra-payroll-create-section-body">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field :label="__('payroll.year')" name="year" required>
                        <input id="year" name="year" type="number" required min="2020" max="2100" value="{{ old('year', $defaultYear) }}" class="ziifra-input !mt-0">
                    </x-form.field>
                    <x-form.field :label="__('payroll.month')" name="month" required>
                        <select id="month" name="month" required class="ziifra-input !mt-0">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected((int) old('month', $defaultMonth) === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                            @endfor
                        </select>
                    </x-form.field>
                </div>
            </div>
        </section>

        <section class="ziifra-payroll-create-section">
            <header class="ziifra-payroll-create-section-head">
                <h2 class="ziifra-payroll-create-section-title">{{ __('payroll.calculation_mode') }}</h2>
                <p class="ziifra-payroll-create-section-desc">{{ __('payroll.calculation_mode_help') }}</p>
            </header>
            <div class="ziifra-payroll-create-section-body">
                <x-form.field :label="__('payroll.calculation_mode')" name="calculation_mode">
                    <select id="calculation_mode" name="calculation_mode" class="ziifra-input !mt-0">
                        <option value="salary" @selected(old('calculation_mode', 'salary') === 'salary')>{{ __('payroll.mode_salary') }}</option>
                        <option value="hourly" @selected(old('calculation_mode') === 'hourly')>{{ __('payroll.mode_hourly') }}</option>
                    </select>
                </x-form.field>
                <p class="ziifra-payroll-create-mode-hint" data-payroll-mode-hint="salary">{{ __('payroll.mode_salary_hint') }}</p>
                <p class="ziifra-payroll-create-mode-hint hidden" data-payroll-mode-hint="hourly">{{ __('payroll.mode_hourly_hint') }}</p>
            </div>
        </section>

        <section class="ziifra-payroll-create-section" data-payroll-audience-section hidden>
            <header class="ziifra-payroll-create-section-head">
                <h2 class="ziifra-payroll-create-section-title">{{ __('payroll.generation_mode') }}</h2>
                <p class="ziifra-payroll-create-section-desc">{{ __('payroll.generation_mode_help') }}</p>
            </header>
            <div class="ziifra-payroll-create-section-body space-y-4">
                <x-form.field :label="__('payroll.generation_mode')" name="generation_mode">
                    <select id="generation_mode" name="generation_mode" class="ziifra-input !mt-0">
                        @foreach ($generationModes as $mode)
                            <option value="{{ $mode->value }}" @selected($generationMode === $mode->value)>{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                <div data-payroll-individual-wrap hidden>
                    <x-form.field :label="__('payroll.generation.individual')" name="employee_id">
                        <select id="employee_id" name="employee_id" class="ziifra-input !mt-0">
                            <option value="">{{ __('documents.select_employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>{{ $employee->fullName() }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                </div>

                <div data-payroll-group-wrap hidden>
                    <div class="ziifra-form-field">
                        <span class="ziifra-label-field">{{ __('payroll.generation.group') }}</span>
                        <p class="mt-1 text-xs text-ziifra-muted">{{ __('payroll.select_employees_hint') }}</p>
                        <div class="ziifra-payroll-create-employee-picker mt-2">
                            @forelse ($employees as $employee)
                                <label class="ziifra-payroll-create-employee-option">
                                    <input
                                        type="checkbox"
                                        name="employee_ids[]"
                                        value="{{ $employee->id }}"
                                        @checked(in_array($employee->id, $selectedEmployeeIds, true))
                                    >
                                    <span class="ziifra-employee-compact-card-avatar !h-8 !w-8 !text-[0.65rem]" aria-hidden="true">{{ $employee->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-ziifra-ink">{{ $employee->fullName() }}</span>
                                        @if ($employee->position?->title)
                                            <span class="block truncate text-xs text-ziifra-muted">{{ $employee->position->title }}</span>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <p class="px-3 py-4 text-sm text-ziifra-muted">{{ __('employees.empty') }}</p>
                            @endforelse
                        </div>
                        <x-form.error name="employee_ids" />
                        <x-form.error name="employee_ids.*" />
                    </div>
                </div>
            </div>
        </section>

        <div class="ziifra-payroll-create-actions">
            <button type="submit" class="ziifra-btn-primary">{{ __('payroll.create_run') }}</button>
            <a href="{{ route('payroll.index') }}" class="ziifra-btn-app-outline" data-page-nav>{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
