@extends('layouts.app')

@section('title', $employee->fullName())
@section('header', $employee->fullName())

@section('content')
@php
    use App\Enums\EmploymentStatus;
@endphp

<div class="ziifra-employee-profile mx-auto max-w-6xl space-y-6">
    <section class="ziifra-dashboard-panel overflow-hidden">
        <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 gap-4">
                <span class="ziifra-dashboard-avatar !h-14 !w-14 shrink-0 !text-base">{{ $employee->initials() }}</span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight text-ziifra-ink">{{ $employee->fullName() }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-green-50 text-green-700' => $employee->employment_status === EmploymentStatus::Active,
                            'bg-amber-50 text-amber-800' => $employee->employment_status === EmploymentStatus::OnLeave,
                            'bg-ziifra-cream text-ziifra-muted' => ! in_array($employee->employment_status, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true),
                        ])>
                            {{ $employee->employment_status->label() }}
                        </span>
                        @if ($employee->employment_type)
                            <span class="inline-flex rounded-full border border-ziifra-line/80 bg-ziifra-cream/80 px-2.5 py-0.5 text-xs font-medium text-ziifra-muted">
                                {{ $employee->employment_type->label() }}
                            </span>
                        @endif
                    </div>
                    <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        @if ($employee->email)
                            <div>
                                <dt class="sr-only">{{ __('employees.field_email') }}</dt>
                                <dd><a href="mailto:{{ $employee->email }}" class="text-ziifra-accent-deep hover:underline">{{ $employee->email }}</a></dd>
                            </div>
                        @endif
                        @if ($employee->phone)
                            <div>
                                <dt class="sr-only">{{ __('employees.field_phone') }}</dt>
                                <dd class="text-ziifra-ink">{{ $employee->phone }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
            @if ($canManage)
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('employees.edit', $employee) }}" class="ziifra-btn-app-outline">{{ __('employees.edit') }}</a>
                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-confirm="{{ __('employees.remove_confirm') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('common.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                            {{ __('employees.remove') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">
            <section class="ziifra-dashboard-panel">
                <div class="ziifra-dashboard-panel-head">
                    <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.section_employment') }}</h2>
                </div>
                <dl class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('employees.field_department') }}</dt>
                        <dd class="mt-1 text-sm text-ziifra-ink">{{ $employee->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('employees.field_position') }}</dt>
                        <dd class="mt-1 text-sm text-ziifra-ink">{{ $employee->position?->title ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('employees.field_manager') }}</dt>
                        <dd class="mt-1 text-sm text-ziifra-ink">
                            @if ($employee->manager)
                                <a href="{{ route('employees.show', $employee->manager) }}" class="font-medium text-ziifra-accent-deep hover:underline">{{ $employee->manager->fullName() }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('employees.field_start_date') }}</dt>
                        <dd class="mt-1 text-sm text-ziifra-ink">{{ $employee->start_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            @if ($employee->fieldValues->isNotEmpty())
                <section class="ziifra-dashboard-panel">
                    <div class="ziifra-dashboard-panel-head">
                        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.section_custom_fields') }}</h2>
                    </div>
                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        @foreach ($employee->fieldValues as $fieldValue)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ $fieldValue->definition->name }}</dt>
                                <dd class="mt-1 text-sm text-ziifra-ink">
                                    @if ($fieldValue->isFile() && $fieldValue->hasStoredFile())
                                        <a href="{{ route('employees.custom-fields.download', [$employee, $fieldValue->definition]) }}" class="font-medium text-ziifra-accent-deep hover:underline">
                                            {{ $fieldValue->displayValue() }}
                                        </a>
                                    @else
                                        {{ $fieldValue->displayValue() }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            @if ($employee->directReports->isNotEmpty())
                <section class="ziifra-dashboard-panel">
                    <div class="ziifra-dashboard-panel-head">
                        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.section_direct_reports') }}</h2>
                        <span class="ziifra-dashboard-badge">{{ $employee->directReports->count() }}</span>
                    </div>
                    <ul class="divide-y divide-ziifra-line/60 p-2">
                        @foreach ($employee->directReports as $report)
                            <li>
                                <a href="{{ route('employees.show', $report) }}" class="ziifra-dashboard-leave-row">
                                    <span class="ziifra-dashboard-avatar">{{ $report->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-medium text-ziifra-ink">{{ $report->fullName() }}</span>
                                        @if ($report->position)
                                            <span class="block text-xs text-ziifra-muted">{{ $report->position->title }}</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @include('app.employees._documents', ['employee' => $employee, 'canManage' => $canManage, 'embedded' => true])
        </div>

        <aside class="space-y-6">
            @include('app.employees._login-access', ['embedded' => true])

            <section class="ziifra-dashboard-panel">
                <div class="ziifra-dashboard-panel-head">
                    <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.section_summary') }}</h2>
                </div>
                <ul class="space-y-3 p-5 text-sm">
                    <li class="flex justify-between gap-3">
                        <span class="text-ziifra-muted">{{ __('employees.field_status') }}</span>
                        <span class="font-medium text-ziifra-ink">{{ $employee->employment_status->label() }}</span>
                    </li>
                    @if ($employee->department)
                        <li class="flex justify-between gap-3">
                            <span class="text-ziifra-muted">{{ __('employees.field_department') }}</span>
                            <span class="font-medium text-ziifra-ink">{{ $employee->department->name }}</span>
                        </li>
                    @endif
                    @if ($employee->start_date)
                        <li class="flex justify-between gap-3">
                            <span class="text-ziifra-muted">{{ __('employees.field_start_date') }}</span>
                            <span class="font-medium text-ziifra-ink">{{ $employee->start_date->format('M j, Y') }}</span>
                        </li>
                    @endif
                </ul>
            </section>
        </aside>
    </div>

    <a href="{{ route('employees.index') }}" class="inline-flex text-sm font-medium text-ziifra-accent-deep hover:underline">
        ← {{ __('employees.back_to_list') }}
    </a>
</div>
@endsection
