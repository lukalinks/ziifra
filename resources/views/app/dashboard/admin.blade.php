@extends('layouts.app')

@section('title', __('admin_dashboard.title'))
@section('header', __('admin_dashboard.header'))

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->format('l, j F Y');
    $officePercent = $activeEmployeeCount > 0
        ? min(100, (int) round(($teamInOfficeCount / $activeEmployeeCount) * 100))
        : 0;
@endphp

<div class="ziifra-dashboard-page ziifra-admin-dashboard">
    {{-- Hero --}}
    <section class="ziifra-dashboard-hero ziifra-dashboard-hero-admin">
        <div class="relative z-[1] flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="ziifra-label !text-ziifra-muted">{{ __('dashboard.today') }}</p>
                    @if ($planName)
                        <span class="ziifra-admin-dashboard-pill">{{ __('dashboard.on_plan', ['plan' => $planName]) }}</span>
                    @endif
                </div>
                <p class="mt-2 font-mono text-xs text-ziifra-muted">{{ $todayLabel }}</p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl lg:text-3xl">
                    {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ziifra-muted">
                    {{ __('admin_dashboard.subtitle', ['company' => $organization->name]) }}
                </p>
            </div>
            <div class="ziifra-admin-dashboard-toolbar-actions shrink-0">
                @if ($pendingLeaveCount > 0)
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="ziifra-btn-primary !py-2.5 !text-sm">
                        {{ __('dashboard.primary_review_leave', ['count' => $pendingLeaveCount]) }}
                    </a>
                @endif
                <a href="{{ route('employees.create') }}" class="ziifra-btn-app-outline !text-sm">{{ __('dashboard.add_employee') }}</a>
                @if ($hasPayroll && $draftPayrollRun)
                    <a href="{{ $draftPayrollRun->showUrl() }}" class="ziifra-btn-app-outline !text-sm">{{ __('dashboard.open_payroll') }}</a>
                @endif
            </div>
        </div>
    </section>

    @if ($canManageOrganization && ! $organization->isProfileComplete())
        <div class="ziifra-dashboard-banner">
            <div class="min-w-0">
                <p class="font-medium text-amber-950">{{ __('dashboard.complete_profile') }}</p>
                <p class="mt-0.5 text-sm text-amber-900/90">{{ __('dashboard.complete_profile_hint') }}</p>
            </div>
            <a href="{{ route('settings.company.edit') }}" class="ziifra-dashboard-banner-btn ziifra-btn-app shrink-0 !bg-amber-900 !text-amber-50 hover:!bg-amber-950">
                {{ __('dashboard.setup_company') }}
            </a>
        </div>
    @endif

    @if (count($priorityAlerts) > 0)
        <div class="ziifra-admin-dashboard-alerts">
            @include('app.dashboard._priority-alerts')
        </div>
    @endif

    {{-- KPI overview --}}
    <x-dashboard.section :title="__('admin_dashboard.overview')" :description="__('admin_dashboard.overview_hint')">
        <div class="ziifra-admin-dashboard-kpis ziifra-admin-dashboard-kpis-all">
            <x-dashboard.stat
                :label="__('dashboard.active_employees')"
                :value="$activeEmployeeCount"
                :href="route('employees.index')"
                icon-tone="accent"
                :trend="__('admin_dashboard.kpi_in_office', ['count' => $teamInOfficeCount, 'total' => $activeEmployeeCount])"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('dashboard.departments')"
                :value="$departmentCount"
                :href="route('settings.departments.index')"
                icon-tone="sky"
                :trend="__('admin_dashboard.kpi_team_members', ['count' => $teamUserCount])"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('dashboard.pending_leave')"
                :value="$pendingLeaveCount"
                :href="route('leave.index', ['status' => 'pending'])"
                :variant="$pendingLeaveCount > 0 ? 'alert' : 'default'"
                icon-tone="amber"
                :trend="$pendingLeaveCount > 0 ? __('admin_dashboard.kpi_pending_review') : __('admin_dashboard.kpi_all_clear')"
                :trend-up="$pendingLeaveCount > 0 ? false : true"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('admin_dashboard.kpi_leave_days')"
                :value="number_format($approvedLeaveDaysMonth, 1)"
                :href="route('leave.index')"
                icon-tone="copper"
                :trend="$newHiresThisMonth > 0 ? __('admin_dashboard.kpi_new_hires', ['count' => $newHiresThisMonth]) : __('admin_dashboard.leave_days_month', ['days' => number_format($approvedLeaveDaysMonth, 1)])"
                :trend-up="$newHiresThisMonth > 0 ? true : null"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('dashboard.active_projects')"
                :value="$activeProjectsCount"
                :href="route('projects.index')"
                icon-tone="accent"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.25-3.75l3 3m0 0l3-3m-3 3V2.25"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('dashboard.hours_this_month')"
                :value="number_format($hoursThisMonth, 1)"
                :href="route('projects.index')"
                icon-tone="sky"
                :trend="$pendingHoursApprovals > 0 ? __('admin_dashboard.kpi_hours_pending', ['count' => $pendingHoursApprovals]) : __('admin_dashboard.kpi_hours_all_approved')"
                :trend-up="$pendingHoursApprovals > 0 ? false : true"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-dashboard.stat>

            <x-dashboard.stat
                :label="__('dashboard.pending_hours_approvals')"
                :value="$pendingHoursApprovals"
                :href="route('projects.index')"
                :variant="$pendingHoursApprovals > 0 ? 'alert' : 'default'"
                icon-tone="amber"
                :trend="$pendingHoursApprovals > 0 ? __('admin_dashboard.kpi_pending_review') : __('admin_dashboard.kpi_all_clear')"
                :trend-up="$pendingHoursApprovals > 0 ? false : true"
            >
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-dashboard.stat>
        </div>
    </x-dashboard.section>

    {{-- Analytics --}}
    <div class="ziifra-admin-dashboard-main">
            <x-dashboard.leave-chart
                class="ziifra-admin-dashboard-chart"
                :labels="$leaveTrendChart['labels']"
                :approved="$leaveTrendChart['approved']"
                :pending="$leaveTrendChart['pending']"
            />

            <aside class="ziifra-admin-dashboard-sidebar">
                <section class="ziifra-dashboard-panel ziifra-admin-dashboard-ring-card">
                    <div class="ziifra-dashboard-panel-head">
                        <div>
                            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('admin_dashboard.workforce_title') }}</h3>
                            <p class="text-xs text-ziifra-muted">{{ __('admin_dashboard.workforce_hint') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center px-5 py-6">
                        <div class="ziifra-dashboard-balance-ring ziifra-admin-dashboard-ring" style="background: conic-gradient(var(--color-ziifra-accent) {{ $officePercent }}%, rgb(226 232 240 / 0.65) 0)">
                            <div class="flex h-[7rem] w-[7rem] flex-col items-center justify-center rounded-full bg-ziifra-paper shadow-inner">
                                <span class="text-3xl font-semibold tabular-nums text-ziifra-ink">{{ $officePercent }}%</span>
                                <span class="mt-0.5 text-[0.65rem] font-medium text-ziifra-muted">{{ __('admin_dashboard.in_office') }}</span>
                            </div>
                        </div>
                        <p class="mt-4 text-center text-sm text-ziifra-muted">
                            {{ __('dashboard.team_presence', ['in' => $teamInOfficeCount, 'total' => $activeEmployeeCount]) }}
                        </p>
                        @if ($employeeLimit)
                            <div class="mt-4 w-full">
                                <div class="flex items-center justify-between text-xs text-ziifra-muted">
                                    <span>{{ __('dashboard.employee_capacity', ['count' => $activeEmployeeCount, 'limit' => $employeeLimit]) }}</span>
                                    <span class="font-medium tabular-nums text-ziifra-ink">{{ $employeeUsagePercent }}%</span>
                                </div>
                                <div class="ziifra-dashboard-meter mt-1.5" role="progressbar" aria-valuenow="{{ $employeeUsagePercent }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="ziifra-dashboard-meter-fill {{ $employeeUsagePercent >= 90 ? 'ziifra-dashboard-meter-fill-warn' : '' }}" style="width: {{ $employeeUsagePercent }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                @if ($expiringDocumentCount > 0 || $employeesMissingLogin > 0)
                    @include('app.dashboard._compliance-snapshot')
                @endif
            </aside>
    </div>

    {{-- Operations --}}
    <div class="ziifra-admin-dashboard-ops">
        <section class="ziifra-dashboard-panel">
            <div class="ziifra-dashboard-panel-head">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('admin_dashboard.pending_approvals') }}</h3>
                    <p class="text-xs text-ziifra-muted">{{ __('admin_dashboard.pending_approvals_hint') }}</p>
                </div>
                @if ($pendingLeaveCount > 0)
                    <span class="ziifra-dashboard-badge">{{ $pendingLeaveCount }}</span>
                @endif
            </div>
            <div class="p-3">
                @if ($pendingLeaveRequests->isEmpty())
                    <x-ui.empty-state :title="__('admin_dashboard.pending_approvals_empty')" />
                @else
                    <ul class="divide-y divide-ziifra-line/60">
                        @foreach ($pendingLeaveRequests as $request)
                            <li>
                                <a href="{{ route('leave.show', $request) }}" class="ziifra-admin-dashboard-queue-row">
                                    <span class="ziifra-dashboard-avatar">{{ $request->employee->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium text-ziifra-ink">{{ $request->employee->fullName() }}</span>
                                        <span class="block truncate text-xs text-ziifra-muted">
                                            {{ $request->leaveType->name }} · {{ number_format($request->days, 1) }} {{ __('leave.days') }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-right text-xs text-ziifra-muted">
                                        {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($pendingLeaveCount > $pendingLeaveRequests->count())
                        <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="mt-3 block text-center text-sm font-medium text-ziifra-accent-deep hover:underline">
                            {{ __('dashboard.view_all_leave') }} →
                        </a>
                    @endif
                @endif
            </div>
        </section>

        <div class="ziifra-admin-dashboard-ops-side">
            @if (count($weekOutlook) > 0)
                <x-dashboard.week-strip :days="$weekOutlook" />
            @endif

            @if (count($quickActions) > 0)
                <section class="ziifra-dashboard-panel">
                    <div class="ziifra-dashboard-panel-head">
                        <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.quick_actions') }}</h3>
                    </div>
                    <div class="p-3 sm:p-4">
                        @include('app.dashboard._quick-actions', ['columns' => 2])
                    </div>
                </section>
            @endif

            @if (count($setupChecklist) > 0)
                <section class="ziifra-dashboard-panel">
                    <div class="ziifra-dashboard-panel-head">
                        <div>
                            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('admin_dashboard.setup_checklist') }}</h3>
                            <p class="text-xs text-ziifra-muted">{{ __('admin_dashboard.setup_checklist_hint') }}</p>
                        </div>
                    </div>
                    <ul class="divide-y divide-ziifra-line/60 p-2">
                        @foreach ($setupChecklist as $item)
                            <li>
                                <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition hover:bg-ziifra-cream/70">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-ziifra-accent/40"></span>
                                    <span class="text-ziifra-ink">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    <x-dashboard.section :title="__('admin_dashboard.team_leave')" :description="__('admin_dashboard.team_leave_hint')">
        @include('app.dashboard._leave-panels')
    </x-dashboard.section>

    @if ($expiringDocuments->isNotEmpty() || $recentHires->isNotEmpty() || $draftPayrollRun !== null)
        <x-dashboard.section :title="__('admin_dashboard.people_and_payroll')">
            @include('app.dashboard._secondary')
        </x-dashboard.section>
    @endif
</div>
@endsection
