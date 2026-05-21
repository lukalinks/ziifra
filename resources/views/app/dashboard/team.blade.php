@extends('layouts.app')

@section('title', __('team_dashboard.title'))
@section('header', __('team_dashboard.header'))

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->format('l, j F Y');
@endphp

<div class="ziifra-dashboard ziifra-dashboard-team space-y-8">
    <section class="ziifra-dashboard-hero ziifra-dashboard-hero-grid">
        <div class="relative z-[1] space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <p class="ziifra-label !text-ziifra-muted">{{ __('dashboard.today') }}</p>
                <span class="font-mono text-xs text-ziifra-muted">{{ $todayLabel }}</span>
            </div>
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ziifra-ink sm:text-3xl">
                    {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ziifra-muted">
                    {{ __('team_dashboard.subtitle', ['company' => $organization->name]) }}
                </p>
            </div>
            @if ($pendingLeaveCount > 0)
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="ziifra-btn-primary !py-2.5 !text-sm">
                        {{ __('dashboard.primary_review_leave', ['count' => $pendingLeaveCount]) }}
                    </a>
                    <a href="{{ route('leave.calendar') }}" class="ziifra-btn-app-outline !rounded-full">{{ __('dashboard.view_calendar') }}</a>
                </div>
            @endif
        </div>
    </section>

    <div class="ziifra-dashboard-stats sm:grid-cols-2 lg:max-w-xl">
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
        </x-dashboard.stat>
        <x-dashboard.stat
            :label="__('dashboard.out_today')"
            :value="$outToday->count()"
            :href="route('leave.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.stat>
    </div>

    @if ($pendingLeaveRequests->isNotEmpty())
        <x-dashboard.section :title="__('team_dashboard.pending_team_leave')">
            <section class="ziifra-dashboard-panel">
                <div class="p-3">
                    <ul class="space-y-0.5">
                        @foreach ($pendingLeaveRequests as $request)
                            <li class="ziifra-dashboard-list-row">
                                <div class="min-w-0">
                                    <p class="font-medium text-ziifra-ink">{{ $request->employee->fullName() }}</p>
                                    <p class="text-xs text-ziifra-muted">
                                        {{ $request->leaveType->name }} · {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j') }}
                                    </p>
                                </div>
                                <a href="{{ route('leave.show', $request) }}" class="shrink-0 text-sm font-medium text-ziifra-accent-deep hover:underline">
                                    {{ __('team_dashboard.review') }} →
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="ziifra-dashboard-panel-foot">
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                        {{ __('dashboard.view_all_leave') }} →
                    </a>
                </div>
            </section>
        </x-dashboard.section>
    @endif

    @if (count($quickActions) > 0)
        <x-dashboard.section :title="__('dashboard.quick_actions')" compact>
            @include('app.dashboard._quick-actions', ['columns' => 3])
        </x-dashboard.section>
    @endif

    @if (count($weekOutlook) > 0)
        <x-dashboard.section :title="__('dashboard.week_ahead')" :description="__('dashboard.week_ahead_subtitle')">
            <x-dashboard.week-strip :days="$weekOutlook" />
        </x-dashboard.section>
    @endif

    @if ($myLeaveBalance)
        @php
            $canManageEmployees = false;
            $canViewEmployees = false;
            $expiringDocuments = collect();
            $expiringDocumentCount = 0;
            $recentHires = collect();
            $draftPayrollRun = null;
        @endphp
        <x-dashboard.section :title="__('dashboard.my_leave')">
            <div class="max-w-sm">
                @include('app.dashboard._secondary')
            </div>
        </x-dashboard.section>
    @endif

    <x-dashboard.section :title="__('admin_dashboard.team_leave')" :description="__('admin_dashboard.team_leave_hint')">
        @include('app.dashboard._leave-panels')
    </x-dashboard.section>
</div>
@endsection
