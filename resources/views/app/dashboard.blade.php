@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->format('l, j F Y');
    $showStats = $canViewEmployees || $canViewLeave || ($canManageEmployees && $expiringDocumentCount > 0);
    $actionsColSpan = $canViewLeave ? 'lg:col-span-1' : 'lg:col-span-3';
    $actionsGrid = $canViewLeave ? 'flex flex-col gap-2' : 'grid gap-2 sm:grid-cols-2 xl:grid-cols-3';
@endphp

<div class="ziifra-dashboard space-y-6 lg:space-y-8">
    {{-- Hero --}}
    <section class="ziifra-dashboard-hero ziifra-dashboard-hero-grid">
        <div class="relative z-[1] flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="ziifra-label !text-ziifra-muted">{{ __('dashboard.today') }}</p>
                    @if ($planName)
                        <span class="rounded-full border border-ziifra-line/80 bg-ziifra-paper/80 px-2.5 py-0.5 text-[0.65rem] font-medium uppercase tracking-wider text-ziifra-muted">
                            {{ __('dashboard.on_plan', ['plan' => $planName]) }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 font-mono text-xs text-ziifra-muted">{{ $todayLabel }}</p>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-ziifra-ink sm:text-3xl">
                    {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ziifra-muted">
                    {{ __('dashboard.welcome_subtitle', ['company' => $organization->name]) }}
                </p>

                @if ($teamInOfficeCount !== null)
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="ziifra-dashboard-insight">
                            {{ __('dashboard.team_presence', ['in' => $teamInOfficeCount, 'total' => $activeEmployeeCount]) }}
                        </span>
                        @if ($newHiresThisMonth > 0)
                            <span class="ziifra-dashboard-insight">
                                {{ __('dashboard.new_hires_month', ['count' => $newHiresThisMonth]) }}
                            </span>
                        @endif
                    </div>
                @endif

                @if ($canManageEmployees && $employeeLimit)
                    <div class="mt-5 max-w-md">
                        <div class="flex items-center justify-between text-xs text-ziifra-muted">
                            <span>{{ __('dashboard.employee_capacity', ['count' => $activeEmployeeCount, 'limit' => $employeeLimit]) }}</span>
                            <span class="font-medium tabular-nums text-ziifra-ink">{{ $employeeUsagePercent }}%</span>
                        </div>
                        <div class="ziifra-dashboard-meter mt-1.5" role="progressbar" aria-valuenow="{{ $employeeUsagePercent }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="ziifra-dashboard-meter-fill {{ $employeeUsagePercent >= 90 ? 'ziifra-dashboard-meter-fill-warn' : '' }}" style="width: {{ $employeeUsagePercent }}%"></div>
                        </div>
                    </div>
                @elseif ($canViewEmployees)
                    <p class="mt-4 text-xs text-ziifra-muted">
                        {{ __('dashboard.employee_capacity_unlimited', ['count' => $activeEmployeeCount]) }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap gap-2">
                    @if ($pendingLeaveCount > 0 && $canViewLeave)
                        <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="ziifra-btn-primary !py-2.5 !text-sm">
                            {{ __('dashboard.primary_review_leave', ['count' => $pendingLeaveCount]) }}
                        </a>
                    @elseif ($canRequestLeave)
                        <a href="{{ route('leave.create') }}" class="ziifra-btn-primary !py-2.5 !text-sm">
                            {{ __('dashboard.primary_request_leave') }}
                        </a>
                    @endif
                    @if ($canManageEmployees)
                        <a href="{{ route('employees.create') }}" class="ziifra-btn-app-outline !rounded-full">
                            {{ __('dashboard.add_employee') }}
                        </a>
                    @endif
                </div>
            </div>

        </div>

        @if ($canManageOrganization && ! $organization->isProfileComplete())
            <div class="relative z-[1] mt-6 flex flex-col gap-3 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium text-amber-950">{{ __('dashboard.complete_profile') }}</p>
                    <p class="mt-0.5 text-sm text-amber-900/90">{{ __('dashboard.complete_profile_hint') }}</p>
                </div>
                <a href="{{ route('settings.index') }}" class="ziifra-btn-app shrink-0 !bg-amber-900 !text-amber-50 hover:!bg-amber-950">
                    {{ __('dashboard.setup_company') }}
                </a>
            </div>
        @endif
    </section>

    {{-- Stats --}}
    @if ($showStats)
        <div class="ziifra-dashboard-stats">
            @if ($canViewEmployees)
                <x-dashboard.stat
                    :label="__('dashboard.active_employees')"
                    :value="$activeEmployeeCount"
                    :href="route('employees.index')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:footer>
                        <span class="text-sm font-medium text-ziifra-accent-deep group-hover:underline">{{ __('dashboard.view_directory') }} →</span>
                    </x-slot:footer>
                </x-dashboard.stat>

                <x-dashboard.stat
                    :label="__('dashboard.departments')"
                    :value="$departmentCount"
                    :href="$canManageEmployees ? route('settings.departments.index') : null"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </x-slot:icon>
                    @if ($canManageEmployees)
                        <x-slot:footer>
                            <span class="text-sm font-medium text-ziifra-accent-deep group-hover:underline">{{ __('dashboard.manage_departments') }} →</span>
                        </x-slot:footer>
                    @endif
                </x-dashboard.stat>
            @endif

            @if ($canViewLeave)
                <x-dashboard.stat
                    :label="__('dashboard.pending_leave')"
                    :value="$pendingLeaveCount"
                    :href="route('leave.index', ['status' => 'pending'])"
                    :variant="$pendingLeaveCount > 0 ? 'alert' : 'default'"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:footer>
                        <span class="text-sm font-medium text-ziifra-accent-deep group-hover:underline">{{ __('dashboard.view_calendar') }} →</span>
                    </x-slot:footer>
                </x-dashboard.stat>
            @endif

            @if ($canManageEmployees && $expiringDocumentCount > 0)
                <x-dashboard.stat
                    :label="__('dashboard.expiring_documents')"
                    :value="$expiringDocumentCount"
                    :href="route('employees.index')"
                    variant="warn"
                    :hint="__('dashboard.expiring_documents_hint')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:footer>
                        <span class="text-sm font-medium text-amber-950 underline group-hover:no-underline">{{ __('dashboard.view_directory') }} →</span>
                    </x-slot:footer>
                </x-dashboard.stat>
            @endif
        </div>
    @endif

    @if ($canViewLeave && count($weekOutlook) > 0)
        <x-dashboard.week-strip :days="$weekOutlook" />
    @endif

    @includeWhen(
        ($myLeaveBalance !== null)
            || ($canManageEmployees && $expiringDocuments->isNotEmpty())
            || ($canViewEmployees && $recentHires->isNotEmpty())
            || ($draftPayrollRun !== null),
        'app.dashboard._secondary'
    )

    {{-- Actions + leave --}}
    @if (count($quickActions) > 0 || $canViewLeave)
        <div class="grid gap-6 lg:grid-cols-3 lg:items-stretch">
            @if (count($quickActions) > 0)
                <aside class="ziifra-dashboard-panel {{ $actionsColSpan }}">
                    <div class="ziifra-dashboard-panel-head">
                        <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.quick_actions') }}</h3>
                    </div>
                    <div class="{{ $actionsGrid }} p-4">
                        @foreach ($quickActions as $action)
                            <x-dashboard.action
                                :href="route($action['route'], $action['params'] ?? [])"
                                :icon="$action['icon']"
                                :label="$action['label']"
                            />
                        @endforeach
                    </div>
                </aside>
            @endif

            @if ($canViewLeave)
                <div class="grid gap-6 {{ count($quickActions) > 0 ? 'lg:col-span-2' : 'lg:col-span-3' }} md:grid-cols-2">
                    <section class="ziifra-dashboard-panel">
                        <div class="ziifra-dashboard-panel-head">
                            <div>
                                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.out_today') }}</h3>
                                <p class="text-xs text-ziifra-muted">
                                    {{ trans_choice('dashboard.people_count', $outToday->count(), ['count' => $outToday->count()]) }}
                                </p>
                            </div>
                            @if ($outToday->isNotEmpty())
                                <span class="ziifra-dashboard-badge">{{ $outToday->count() }}</span>
                            @endif
                        </div>
                        <div class="flex min-h-[12rem] flex-1 flex-col p-3">
                            @if ($outToday->isEmpty())
                                <div class="ziifra-dashboard-empty flex-1">
                                    <span class="ziifra-dashboard-empty-icon text-ziifra-accent/70">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm text-ziifra-muted">{{ __('dashboard.out_today_empty') }}</p>
                                </div>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach ($outToday as $request)
                                        <li>
                                            <x-dashboard.leave-row
                                                :request="$request"
                                                :badge="__('dashboard.until', ['date' => $request->end_date->format('M j')])"
                                            />
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="ziifra-dashboard-panel-foot">
                            <a href="{{ route('leave.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                                {{ __('dashboard.view_all_leave') }} →
                            </a>
                        </div>
                    </section>

                    <section class="ziifra-dashboard-panel">
                        <div class="ziifra-dashboard-panel-head">
                            <div>
                                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.upcoming_leave') }}</h3>
                                <p class="text-xs text-ziifra-muted">{{ __('dashboard.upcoming_leave_subtitle') }}</p>
                            </div>
                            @if ($upcomingLeave->isNotEmpty())
                                <span class="ziifra-dashboard-badge">{{ $upcomingLeave->count() }}</span>
                            @endif
                        </div>
                        <div class="flex min-h-[12rem] flex-1 flex-col p-3">
                            @if ($upcomingLeave->isEmpty())
                                <div class="ziifra-dashboard-empty flex-1">
                                    <span class="ziifra-dashboard-empty-icon text-ziifra-muted/40">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm text-ziifra-muted">{{ __('dashboard.upcoming_leave_empty') }}</p>
                                </div>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach ($upcomingLeave as $request)
                                        <li>
                                            <x-dashboard.leave-row :request="$request">
                                                <span class="rounded-full bg-ziifra-accent/12 px-2 py-0.5 text-xs font-medium text-ziifra-accent-deep">
                                                    {{ __('dashboard.starts', ['date' => $request->start_date->format('M j')]) }}
                                                </span>
                                            </x-dashboard.leave-row>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="ziifra-dashboard-panel-foot">
                            <a href="{{ route('leave.calendar') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                                {{ __('dashboard.view_calendar') }} →
                            </a>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
