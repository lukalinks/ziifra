@extends('layouts.app')

@section('title', $employee->fullName())
@section('header', $employee->fullName())

@section('content')
@php
    use App\Enums\EmploymentStatus;
    use App\Enums\EmployeeLoginStatus;

    $expiringDocumentsCount = $employee->documents->filter(fn ($doc) => $doc->isExpiringSoon() || $doc->isExpired())->count();
    $tenureLabel = $employee->start_date
        ? $employee->start_date->diffForHumans(now(), ['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE])
        : null;
    $workCount = $employee->directReports->count() + $employee->projects->count();
    $roleLine = collect([$employee->position?->title, $employee->department?->name])->filter()->implode(' · ');
@endphp

<div class="ziifra-dashboard-page ziifra-employee-shell">
    <a href="{{ route('employees.index') }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('employees.back_to_list') }}
    </a>

    <section class="ziifra-employee-shell-hero">
        <div class="ziifra-employee-hero-card">
            <div class="ziifra-employee-hero-main">
                <span class="ziifra-employee-masthead-avatar ziifra-employee-masthead-avatar--compact" aria-hidden="true">{{ $employee->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span @class([
                            'ziifra-employee-badge',
                            'ziifra-employee-badge-success' => $employee->employment_status === EmploymentStatus::Active,
                            'ziifra-employee-badge-warning' => $employee->employment_status === EmploymentStatus::OnLeave,
                            'ziifra-employee-badge-muted' => ! in_array($employee->employment_status, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true),
                        ])>{{ $employee->employment_status->label() }}</span>
                        @if ($employee->employment_type)
                            <span class="ziifra-employee-profile-chip">{{ $employee->employment_type->label() }}</span>
                        @endif
                        @if ($loginStatus === EmployeeLoginStatus::Active)
                            <span class="ziifra-employee-badge ziifra-employee-badge-success">{{ $loginStatus->label() }}</span>
                        @elseif ($loginStatus === EmployeeLoginStatus::PendingInvitation)
                            <span class="ziifra-employee-badge ziifra-employee-badge-warning">{{ $loginStatus->label() }}</span>
                        @endif
                    </div>
                    <h1 class="mt-1.5 text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ $employee->fullName() }}</h1>
                    <p class="mt-0.5 text-sm text-ziifra-muted">{{ $roleLine ?: __('employees.section_employment') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @if ($employee->employee_code)
                            <span class="ziifra-employee-hero-chip">{{ $employee->displayCode() }}</span>
                        @endif
                        @if ($employee->email)
                            <a href="mailto:{{ $employee->email }}" class="ziifra-employee-hero-chip ziifra-employee-hero-chip-link">{{ $employee->email }}</a>
                        @endif
                        @if ($employee->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $employee->phone) }}" class="ziifra-employee-hero-chip ziifra-employee-hero-chip-link">{{ $employee->phone }}</a>
                        @endif
                    </div>
                </div>
            </div>
            @if ($canManage)
                <div class="ziifra-employee-shell-actions">
                    <a href="{{ route('employees.edit', $employee) }}" class="ziifra-btn-app !py-2 !text-sm" data-page-nav>{{ __('employees.edit') }}</a>
                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-confirm="{{ __('employees.remove_confirm') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('common.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ziifra-employee-profile-danger-btn !py-2 !text-sm">{{ __('employees.remove') }}</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="ziifra-employee-masthead-metrics ziifra-employee-masthead-metrics--compact">
            @if ($employee->start_date)
                <button type="button" class="ziifra-employee-metric" data-employee-tab-target="overview">
                    <span class="ziifra-employee-metric-label">{{ __('employees.field_start_date') }}</span>
                    <span class="ziifra-employee-metric-value">{{ $employee->start_date->format('M j, Y') }}</span>
                    @if ($tenureLabel)
                        <span class="ziifra-employee-metric-hint">{{ __('employees.tenure_hint', ['duration' => $tenureLabel]) }}</span>
                    @endif
                </button>
            @endif
            <button type="button" class="ziifra-employee-metric" data-employee-tab-target="work">
                <span class="ziifra-employee-metric-label">{{ __('employees.section_projects') }}</span>
                <span class="ziifra-employee-metric-value">{{ $employee->projects->count() }}</span>
            </button>
            <button type="button" class="ziifra-employee-metric" data-employee-tab-target="work">
                <span class="ziifra-employee-metric-label">{{ __('employees.section_direct_reports') }}</span>
                <span class="ziifra-employee-metric-value">{{ $employee->directReports->count() }}</span>
            </button>
            <button type="button" @class(['ziifra-employee-metric', 'ziifra-employee-metric-warn' => $expiringDocumentsCount > 0]) data-employee-tab-target="documents">
                <span class="ziifra-employee-metric-label">{{ __('documents.title') }}</span>
                <span class="ziifra-employee-metric-value">{{ $employee->documents->count() }}</span>
                @if ($expiringDocumentsCount > 0)
                    <span class="ziifra-employee-metric-hint">{{ trans_choice('employees.expiring_documents_count', $expiringDocumentsCount, ['count' => $expiringDocumentsCount]) }}</span>
                @endif
            </button>
        </div>
    </section>

    <section class="ziifra-employee-workspace" data-employee-profile-tabs>
        <nav class="ziifra-employee-workspace-tabs" role="tablist" aria-label="{{ $employee->fullName() }}">
            <button type="button" role="tab" class="ziifra-employee-workspace-tab ziifra-employee-workspace-tab-active" data-employee-tab="overview" aria-selected="true">{{ __('employees.tab_overview') }}</button>
            <button type="button" role="tab" class="ziifra-employee-workspace-tab" data-employee-tab="work" aria-selected="false">
                {{ __('employees.tab_work') }}
                @if ($workCount > 0)
                    <span class="ziifra-employee-workspace-tab-badge">{{ $workCount }}</span>
                @endif
            </button>
            <button type="button" role="tab" class="ziifra-employee-workspace-tab" data-employee-tab="documents" aria-selected="false">
                {{ __('employees.tab_documents') }}
                @if ($employee->documents->isNotEmpty())
                    <span @class(['ziifra-employee-workspace-tab-badge', 'ziifra-employee-workspace-tab-badge-warn' => $expiringDocumentsCount > 0])>{{ $employee->documents->count() }}</span>
                @endif
            </button>
            <button type="button" role="tab" class="ziifra-employee-workspace-tab" data-employee-tab="access" aria-selected="false">{{ __('employees.tab_access') }}</button>
        </nav>

        <div class="ziifra-employee-workspace-panels">
            <div class="ziifra-employee-workspace-panel" data-employee-panel="overview" role="tabpanel">
                <header class="ziifra-employee-panel-head ziifra-employee-panel-head--compact">
                    <h2 class="ziifra-employee-panel-title">{{ __('employees.section_employment') }}</h2>
                </header>
                <div class="ziifra-employee-info-grid ziifra-employee-info-grid--compact">
                    <div class="ziifra-employee-info-item">
                        <span class="ziifra-employee-info-label">{{ __('employees.field_department') }}</span>
                        <span class="ziifra-employee-info-value">{{ $employee->department?->name ?? '—' }}</span>
                    </div>
                    <div class="ziifra-employee-info-item">
                        <span class="ziifra-employee-info-label">{{ __('employees.field_position') }}</span>
                        <span class="ziifra-employee-info-value">{{ $employee->position?->title ?? '—' }}</span>
                    </div>
                    <div class="ziifra-employee-info-item">
                        <span class="ziifra-employee-info-label">{{ __('employees.field_manager') }}</span>
                        <span class="ziifra-employee-info-value">
                            @if ($employee->manager)
                                <a href="{{ route('employees.show', $employee->manager) }}" class="text-ziifra-accent-deep hover:underline" data-page-nav>{{ $employee->manager->fullName() }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="ziifra-employee-info-item">
                        <span class="ziifra-employee-info-label">{{ __('employees.field_start_date') }}</span>
                        <span class="ziifra-employee-info-value">{{ $employee->start_date?->format('M j, Y') ?? '—' }}</span>
                    </div>
                    @if ($canManage && $employee->gross_salary)
                        <div class="ziifra-employee-info-item ziifra-employee-info-item-highlight">
                            <span class="ziifra-employee-info-label">{{ __('employees.field_gross_salary') }}</span>
                            <span class="ziifra-employee-info-value">{{ number_format((float) $employee->gross_salary, 2) }}</span>
                        </div>
                    @endif
                </div>

                @if ($employee->fieldValues->isNotEmpty())
                    <div class="ziifra-employee-workspace-subsection">
                        <h3 class="ziifra-employee-workspace-subtitle">{{ __('employees.section_custom_fields') }}</h3>
                        <div class="ziifra-employee-info-grid ziifra-employee-info-grid--compact">
                            @foreach ($employee->fieldValues as $fieldValue)
                                <div class="ziifra-employee-info-item">
                                    <span class="ziifra-employee-info-label">{{ $fieldValue->definition->name }}</span>
                                    <span class="ziifra-employee-info-value">
                                        @if ($fieldValue->isFile() && $fieldValue->hasStoredFile())
                                            <a href="{{ route('employees.custom-fields.download', [$employee, $fieldValue->definition]) }}" class="text-ziifra-accent-deep hover:underline">
                                                {{ $fieldValue->displayValue() }}
                                            </a>
                                        @else
                                            {{ $fieldValue->displayValue() }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="ziifra-employee-workspace-panel" data-employee-panel="work" role="tabpanel" hidden>
                <header class="ziifra-employee-panel-head ziifra-employee-panel-head--compact">
                    <h2 class="ziifra-employee-panel-title">{{ __('employees.tab_work') }}</h2>
                </header>

                @if ($employee->directReports->isNotEmpty())
                    <div class="ziifra-employee-workspace-subsection ziifra-employee-workspace-subsection-first">
                        <h3 class="ziifra-employee-workspace-subtitle">{{ __('employees.section_direct_reports') }}</h3>
                        <div class="ziifra-employee-work-grid">
                            @foreach ($employee->directReports as $report)
                                <a href="{{ route('employees.show', $report) }}" class="ziifra-employee-work-card" data-page-nav>
                                    <span class="ziifra-employee-compact-card-avatar" aria-hidden="true">{{ $report->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $report->fullName() }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $report->position?->title ?? '—' }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($employee->projects->isNotEmpty())
                    <div @class(['ziifra-employee-workspace-subsection', 'ziifra-employee-workspace-subsection-first' => $employee->directReports->isEmpty()])>
                        <h3 class="ziifra-employee-workspace-subtitle">{{ __('employees.section_projects') }}</h3>
                        <div class="ziifra-employee-work-grid">
                            @foreach ($employee->projects as $project)
                                <a href="{{ route('projects.show', $project) }}" class="ziifra-employee-work-card" data-page-nav>
                                    <span class="ziifra-employee-compact-card-avatar !bg-sky-500/12 !text-sky-800" aria-hidden="true">{{ mb_strtoupper(mb_substr($project->name, 0, 1)) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $project->name }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $project->status->label() }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($workCount === 0)
                    <div class="ziifra-dashboard-empty py-8">
                        <p class="text-sm text-ziifra-muted">{{ __('employees.project_assignments_hint') }}</p>
                        @if ($canManage)
                            <a href="{{ route('employees.edit', $employee) }}" class="ziifra-btn-primary mt-3 !text-sm" data-page-nav>{{ __('employees.edit') }}</a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="ziifra-employee-workspace-panel" data-employee-panel="documents" role="tabpanel" hidden>
                @include('app.employees._documents', ['employee' => $employee, 'canManage' => $canManage, 'embedded' => true])
            </div>

            <div class="ziifra-employee-workspace-panel" data-employee-panel="access" role="tabpanel" hidden>
                @include('app.employees._login-access', ['embedded' => true])

                @if ($canManage)
                    <section class="ziifra-employee-rates-panel">
                        <div class="ziifra-employee-rates-head">
                            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.hourly_rates') }}</h2>
                        </div>
                        <div class="p-4">
                            @if ($employee->hourlyRates->isNotEmpty())
                                <ul class="ziifra-employee-rates-list">
                                    @foreach ($employee->hourlyRates as $rate)
                                        <li class="ziifra-employee-rates-row">
                                            <span>{{ \Carbon\Carbon::create($rate->year, $rate->month, 1)->format('F Y') }}</span>
                                            <span class="font-semibold tabular-nums text-ziifra-ink">{{ number_format((float) $rate->hourly_rate, 2) }} {{ $rate->currency }}/h</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-ziifra-muted">{{ __('employees.no_hourly_rates') }}</p>
                            @endif
                            <form method="POST" action="{{ route('employees.hourly-rates.store', $employee) }}" class="ziifra-employee-profile-rate-form mt-3 border-t border-ziifra-line/60 pt-3">
                                @csrf
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <input type="number" name="year" required min="2020" max="2100" value="{{ now()->year }}" placeholder="{{ __('payroll.year') }}" class="ziifra-input !py-2 !text-sm" aria-label="{{ __('payroll.year') }}">
                                    <input type="number" name="month" required min="1" max="12" value="{{ now()->month }}" placeholder="{{ __('payroll.month') }}" class="ziifra-input !py-2 !text-sm" aria-label="{{ __('payroll.month') }}">
                                    <input type="number" name="hourly_rate" required min="0" step="0.01" placeholder="{{ __('employees.hourly_rate') }}" class="ziifra-input !py-2 !text-sm sm:col-span-2" aria-label="{{ __('employees.hourly_rate') }}">
                                </div>
                                <button type="submit" class="ziifra-btn-primary mt-2 w-full !py-2 !text-sm">{{ __('employees.add_rate') }}</button>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
