@extends('layouts.app')

@section('title', __('employee_dashboard.title'))
@section('header', __('employee_dashboard.header'))

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->locale(app()->getLocale())->translatedFormat('l, j M Y');
    $initials = $linkedEmployee?->initials() ?? collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $roleLine = $linkedEmployee
        ? collect([$linkedEmployee->position?->title, $linkedEmployee->department?->name])->filter()->implode(' · ')
        : null;
@endphp

<div class="ziifra-dashboard-page ziifra-dashboard ziifra-dashboard-employee ziifra-portal-home mx-auto max-w-5xl">
    <header class="ziifra-portal-top">
        <div class="ziifra-portal-top-main">
            <span class="ziifra-portal-top-avatar" aria-hidden="true">{{ $initials }}</span>
            <div class="min-w-0 flex-1">
                <p class="ziifra-portal-top-date">{{ $todayLabel }}</p>
                <h2 class="ziifra-portal-top-title">
                    {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                </h2>
                @if ($roleLine)
                    <p class="ziifra-portal-top-meta">{{ $roleLine }}</p>
                @else
                    <p class="ziifra-portal-top-meta">{{ __('employee_dashboard.subtitle', ['company' => $organization->name]) }}</p>
                @endif
            </div>
        </div>
        @if ($hasEmployeeProfile)
            <div class="ziifra-portal-top-actions">
                @if ($linkedEmployee)
                    <a href="{{ route('employees.show', $linkedEmployee) }}" data-page-nav class="ziifra-btn-app-outline !py-2 !text-sm">
                        {{ __('employee_dashboard.view_profile') }}
                    </a>
                @endif
                <a href="{{ route('leave.create') }}" data-page-nav class="ziifra-btn-primary !py-2 !text-sm">
                    {{ __('employee_dashboard.request_leave') }}
                </a>
            </div>
        @endif
    </header>

    @unless ($hasEmployeeProfile)
        <div class="ziifra-portal-alert">
            {{ __('employee_dashboard.profile_link_hint') }}
        </div>
    @endunless

    @if ($hasEmployeeProfile)
        @if ($onLeaveToday)
            <p class="ziifra-portal-status ziifra-portal-status-leave">
                {{ __('employee_dashboard.on_leave_today') }}
            </p>
        @endif

        <div class="ziifra-portal-kpis">
            @if ($myLeaveBalance)
                <div class="ziifra-portal-kpi">
                    <span class="ziifra-portal-kpi-label">{{ __('employee_dashboard.kpi_balance') }}</span>
                    <span class="ziifra-portal-kpi-value">{{ number_format($myLeaveBalance['remaining'], 0) }}</span>
                    <span class="ziifra-portal-kpi-hint">{{ trans_choice('employee_dashboard.leave_balance_chip', (int) round($myLeaveBalance['remaining']), ['count' => (int) round($myLeaveBalance['remaining'])]) }}</span>
                </div>
            @endif
            <div @class(['ziifra-portal-kpi', 'ziifra-portal-kpi-warn' => $pendingLeaveCount > 0])>
                <span class="ziifra-portal-kpi-label">{{ __('employee_dashboard.kpi_pending') }}</span>
                <span class="ziifra-portal-kpi-value">{{ $pendingLeaveCount }}</span>
                @if ($pendingLeaveCount > 0)
                    <span class="ziifra-portal-kpi-hint">{{ trans_choice('employee_dashboard.pending_requests_count', $pendingLeaveCount, ['count' => $pendingLeaveCount]) }}</span>
                @endif
            </div>
            <div class="ziifra-portal-kpi">
                <span class="ziifra-portal-kpi-label">{{ __('employee_dashboard.kpi_next') }}</span>
                @if ($myNextLeave)
                    <span class="ziifra-portal-kpi-value">{{ $myNextLeave->start_date->format('M j') }}</span>
                    <span class="ziifra-portal-kpi-hint">{{ __('employee_dashboard.next_leave_on', [
                        'type' => $myNextLeave->leaveType->name,
                        'dates' => $myNextLeave->start_date->format('M j').' – '.$myNextLeave->end_date->format('M j'),
                    ]) }}</span>
                @else
                    <span class="ziifra-portal-kpi-value">—</span>
                    <span class="ziifra-portal-kpi-hint">{{ __('employee_dashboard.kpi_none') }}</span>
                @endif
            </div>
        </div>

        @if (count($portalShortcuts) > 0)
            <section class="ziifra-portal-shortcuts-section">
                <h3 class="ziifra-portal-section-title">{{ __('employee_dashboard.quick_access') }}</h3>
                <div class="ziifra-portal-shortcuts">
                    @foreach ($portalShortcuts as $item)
                        <a href="{{ $item['href'] ?? route($item['route']) }}"
                            @if (empty($item['href'])) data-page-nav @endif
                            class="ziifra-portal-shortcut">
                            <span class="ziifra-portal-shortcut-icon">
                                <x-nav-icon :route="$item['route'] ?? 'custom'" />
                            </span>
                            <span class="min-w-0">
                                <span class="ziifra-portal-shortcut-label">{{ $item['label'] }}</span>
                                <span class="ziifra-portal-shortcut-hint">{{ $item['hint'] ?? __('employee_dashboard.open_module') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ziifra-portal-leave-panel">
            <div class="ziifra-portal-leave-panel-head">
                <div>
                    <h3 class="ziifra-portal-section-title !mb-0">{{ __('employee_dashboard.my_requests') }}</h3>
                    <p class="ziifra-portal-section-subtitle">{{ __('employee_dashboard.my_requests_subtitle') }}</p>
                </div>
                <a href="{{ route('leave.index') }}" data-page-nav class="ziifra-portal-link">
                    {{ __('employee_dashboard.view_all_leave') }}
                </a>
            </div>

            @if ($myLeaveBalance)
                <div class="ziifra-portal-balance-strip">
                    @php
                        $usedPercent = $myLeaveBalance['entitled'] > 0
                            ? min(100, (int) round(($myLeaveBalance['used'] / $myLeaveBalance['entitled']) * 100))
                            : 0;
                    @endphp
                    <div class="ziifra-portal-balance-bar" style="--used: {{ $usedPercent }}%">
                        <span class="ziifra-portal-balance-fill"></span>
                    </div>
                    <p class="ziifra-portal-balance-copy">
                        <strong>{{ number_format($myLeaveBalance['remaining'], 0) }}</strong>
                        {{ __('dashboard.days_remaining_label') }}
                        <span class="text-ziifra-muted">· {{ $myLeaveBalance['type'] }} · {{ __('dashboard.days_used', [
                            'used' => number_format($myLeaveBalance['used'], 1),
                            'total' => number_format($myLeaveBalance['entitled'], 0),
                        ]) }}</span>
                    </p>
                </div>
            @endif

            @if ($myLeaveRequests->isEmpty())
                <div class="ziifra-portal-empty">
                    <p>{{ __('employee_dashboard.my_requests_empty') }}</p>
                    <a href="{{ route('leave.create') }}" data-page-nav class="ziifra-btn-app mt-3">{{ __('employee_dashboard.request_leave') }}</a>
                </div>
            @else
                <ul class="ziifra-portal-request-list">
                    @foreach ($myLeaveRequests as $request)
                        <li>
                            <a href="{{ route('leave.show', $request) }}" data-page-nav class="ziifra-portal-request-row">
                                <span class="ziifra-portal-request-type">{{ $request->leaveType->name }}</span>
                                <span class="ziifra-portal-request-dates">
                                    {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j') }}
                                    · {{ number_format($request->days, 1) }} {{ __('employee_dashboard.days') }}
                                </span>
                                <span @class([
                                    'ziifra-portal-request-status',
                                    'ziifra-portal-request-status-pending' => $request->status === \App\Enums\LeaveRequestStatus::Pending,
                                    'ziifra-portal-request-status-approved' => $request->status === \App\Enums\LeaveRequestStatus::Approved,
                                    'ziifra-portal-request-status-rejected' => $request->status === \App\Enums\LeaveRequestStatus::Rejected,
                                ])>{{ $request->status->label() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
@endsection
