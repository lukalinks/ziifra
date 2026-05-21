@extends('layouts.app')

@section('title', __('admin_dashboard.title'))
@section('header', __('admin_dashboard.header'))

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->format('l, j F Y');
@endphp

<div class="ziifra-dashboard ziifra-dashboard-admin space-y-8">
    <section class="ziifra-dashboard-hero ziifra-dashboard-hero-admin ziifra-dashboard-hero-grid">
        <div class="relative z-[1] space-y-5">
            <div class="flex flex-wrap items-center gap-2">
                @if ($role !== \App\Enums\OrganizationRole::Owner)
                    <p class="ziifra-label !text-ziifra-accent-deep">{{ $roleLabel }}</p>
                @endif
                @if ($planName)
                    <span class="rounded-full border border-ziifra-line/80 bg-ziifra-paper/80 px-2.5 py-0.5 text-[0.65rem] font-medium uppercase tracking-wider text-ziifra-muted">
                        {{ __('dashboard.on_plan', ['plan' => $planName]) }}
                    </span>
                @endif
                <span class="font-mono text-xs text-ziifra-muted">{{ $todayLabel }}</span>
            </div>

            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ziifra-ink sm:text-3xl">
                    {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ziifra-muted">
                    {{ __('admin_dashboard.subtitle', ['company' => $organization->name]) }}
                </p>
            </div>

            <div class="ziifra-dashboard-insights">
                <span class="ziifra-dashboard-insight">
                    {{ __('dashboard.team_presence', ['in' => $teamInOfficeCount, 'total' => $activeEmployeeCount]) }}
                </span>
                @if ($newHiresThisMonth > 0)
                    <span class="ziifra-dashboard-insight">{{ __('dashboard.new_hires_month', ['count' => $newHiresThisMonth]) }}</span>
                @endif
                <span class="ziifra-dashboard-insight">
                    {{ __('admin_dashboard.leave_days_month', ['days' => number_format($approvedLeaveDaysMonth, 1)]) }}
                </span>
                <span class="ziifra-dashboard-insight">
                    {{ __('admin_dashboard.team_members', ['count' => $teamUserCount]) }}
                </span>
            </div>

            @if ($employeeLimit)
                <div class="max-w-md">
                    <div class="flex items-center justify-between text-xs text-ziifra-muted">
                        <span>{{ __('dashboard.employee_capacity', ['count' => $activeEmployeeCount, 'limit' => $employeeLimit]) }}</span>
                        <span class="font-medium tabular-nums text-ziifra-ink">{{ $employeeUsagePercent }}%</span>
                    </div>
                    <div class="ziifra-dashboard-meter mt-1.5" role="progressbar" aria-valuenow="{{ $employeeUsagePercent }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="ziifra-dashboard-meter-fill {{ $employeeUsagePercent >= 90 ? 'ziifra-dashboard-meter-fill-warn' : '' }}" style="width: {{ $employeeUsagePercent }}%"></div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 pt-1">
                @if ($trialDaysRemaining !== null && $canManageBilling)
                    <a href="{{ route('settings.billing') }}#plans" class="inline-flex items-center justify-center rounded-full bg-ziifra-accent px-5 py-2.5 text-sm font-semibold text-ziifra-on-accent shadow-sm hover:bg-ziifra-accent-glow">
                        {{ __('billing.upgrade') }}
                    </a>
                @endif
                @if ($pendingLeaveCount > 0)
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="ziifra-btn-primary !py-2.5 !text-sm">
                        {{ __('dashboard.primary_review_leave', ['count' => $pendingLeaveCount]) }}
                    </a>
                @endif
                <a href="{{ route('employees.create') }}" class="ziifra-btn-app-outline !rounded-full">{{ __('dashboard.add_employee') }}</a>
                @if ($hasPayroll && $draftPayrollRun)
                    <a href="{{ $draftPayrollRun->showUrl() }}" class="ziifra-btn-app-outline !rounded-full">{{ __('dashboard.open_payroll') }}</a>
                @endif
            </div>
        </div>

        @if ($canManageOrganization && ! $organization->isProfileComplete())
            <div class="relative z-[1] mt-6 flex flex-col gap-3 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium text-amber-950">{{ __('dashboard.complete_profile') }}</p>
                    <p class="mt-0.5 text-sm text-amber-900/90">{{ __('dashboard.complete_profile_hint') }}</p>
                </div>
                <a href="{{ route('settings.company.edit') }}" class="ziifra-btn-app shrink-0 !bg-amber-900 !text-amber-50 hover:!bg-amber-950">
                    {{ __('dashboard.setup_company') }}
                </a>
            </div>
        @endif
    </section>

    @if (count($priorityAlerts) > 0)
        <x-dashboard.section :title="__('admin_dashboard.needs_attention')">
            @include('app.dashboard._priority-alerts')
        </x-dashboard.section>
    @endif

    @if (count($quickActions) > 0)
        <x-dashboard.section :title="__('dashboard.quick_actions')" compact>
            @include('app.dashboard._quick-actions', ['columns' => 4])
        </x-dashboard.section>
    @endif

    <x-dashboard.section :title="__('admin_dashboard.overview')" :description="__('admin_dashboard.overview_hint')">
        <div class="ziifra-dashboard-stats">
            <x-dashboard.stat :label="__('dashboard.active_employees')" :value="$activeEmployeeCount" :href="route('employees.index')">
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></x-slot:icon>
            </x-dashboard.stat>
            <x-dashboard.stat :label="__('dashboard.departments')" :value="$departmentCount" :href="route('settings.departments.index')">
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></x-slot:icon>
            </x-dashboard.stat>
            <x-dashboard.stat :label="__('dashboard.pending_leave')" :value="$pendingLeaveCount" :href="route('leave.index', ['status' => 'pending'])" :variant="$pendingLeaveCount > 0 ? 'alert' : 'default'">
                <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
            </x-dashboard.stat>
            @if ($expiringDocumentCount > 0)
                <x-dashboard.stat :label="__('dashboard.expiring_documents')" :value="$expiringDocumentCount" :href="route('employees.index')" variant="warn" :hint="__('dashboard.expiring_documents_hint')" />
            @endif
            @if ($employeesMissingLogin > 0)
                <x-dashboard.stat
                    :label="__('admin_dashboard.stat_missing_login')"
                    :value="$employeesMissingLogin"
                    :href="route('employees.index', ['missing_login' => 1])"
                    variant="alert"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat>
            @endif
        </div>
    </x-dashboard.section>

    <x-dashboard.leave-chart
        :labels="$leaveTrendChart['labels']"
        :approved="$leaveTrendChart['approved']"
        :pending="$leaveTrendChart['pending']"
    />

    <div class="grid gap-8 xl:grid-cols-12 xl:items-start">
        <div class="space-y-8 xl:col-span-4">
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
                        <ul class="space-y-2">
                            @foreach ($pendingLeaveRequests as $request)
                                <li class="rounded-xl border border-ziifra-line/80 bg-ziifra-cream/30 p-3">
                                    <p class="font-medium text-ziifra-ink">{{ $request->employee->fullName() }}</p>
                                    <p class="text-xs text-ziifra-muted">
                                        {{ $request->leaveType->name }} · {{ number_format($request->days, 1) }} days
                                    </p>
                                    <p class="mt-1 text-xs text-ziifra-muted">
                                        {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j') }}
                                    </p>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span class="text-[0.65rem] text-ziifra-muted">
                                            {{ __('admin_dashboard.submitted', ['date' => $request->created_at->diffForHumans()]) }}
                                        </span>
                                        <a href="{{ route('leave.show', $request) }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                                            {{ __('admin_dashboard.review_request') }} →
                                        </a>
                                    </div>
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
                                <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-ziifra-cream/70">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-ziifra-accent/40"></span>
                                    <span class="text-ziifra-ink">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="space-y-8 xl:col-span-8">
            @if (count($weekOutlook) > 0)
                <x-dashboard.section :title="__('dashboard.week_ahead')" :description="__('dashboard.week_ahead_subtitle')">
                    <x-dashboard.week-strip :days="$weekOutlook" />
                </x-dashboard.section>
            @endif

            <x-dashboard.section :title="__('admin_dashboard.team_leave')" :description="__('admin_dashboard.team_leave_hint')">
                @include('app.dashboard._leave-panels')
            </x-dashboard.section>

            @if ($expiringDocuments->isNotEmpty() || $recentHires->isNotEmpty() || $draftPayrollRun !== null)
                <x-dashboard.section :title="__('admin_dashboard.people_and_payroll')">
                    @include('app.dashboard._secondary')
                </x-dashboard.section>
            @endif
        </div>
    </div>
</div>
@endsection
