@extends('layouts.app')

@section('title', __('employee_dashboard.title'))
@section('header', __('employee_dashboard.header'))

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    $tz = $organization->timezone ?? config('app.timezone');
    $todayLabel = now()->timezone($tz)->locale(app()->getLocale())->translatedFormat('l, j F Y');
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="ziifra-dashboard-page ziifra-dashboard ziifra-dashboard-employee mx-auto max-w-5xl">
    <section class="ziifra-employee-hero">
        <div class="relative z-[1] flex flex-col gap-5">
            <div class="flex min-w-0 items-start gap-4">
                <span class="ziifra-employee-avatar" aria-hidden="true">{{ $initials }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-xs uppercase tracking-wider text-ziifra-muted">{{ $todayLabel }}</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl lg:text-3xl">
                        {{ __('dashboard.welcome', ['greeting' => $greeting, 'name' => $firstName]) }}
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-ziifra-muted">
                        {{ __('employee_dashboard.subtitle', ['company' => $organization->name]) }}
                    </p>
                    @if ($hasEmployeeProfile && $pendingLeaveCount > 0)
                        <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-200/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ trans_choice('employee_dashboard.pending_requests_count', $pendingLeaveCount, ['count' => $pendingLeaveCount]) }}
                        </p>
                    @endif
                </div>
            </div>
            @if ($hasEmployeeProfile)
                <a href="{{ route('leave.create') }}" data-page-nav class="ziifra-btn-primary w-full justify-center sm:w-auto sm:self-start">
                    {{ __('employee_dashboard.request_leave') }}
                </a>
            @endif
        </div>
    </section>

    @unless ($hasEmployeeProfile)
        <div class="rounded-2xl border border-amber-200/90 bg-amber-50/90 px-5 py-4 text-sm leading-relaxed text-amber-950">
            {{ __('employee_dashboard.profile_link_hint') }}
        </div>
    @endunless

    @if ($hasEmployeeProfile)
        @if (count($portalShortcuts) > 0)
            <section>
                <h3 class="mb-3 text-sm font-semibold text-ziifra-ink">{{ __('employee_dashboard.quick_access') }}</h3>
                <div class="ziifra-employee-shortcuts">
                    @foreach ($portalShortcuts as $item)
                        <a href="{{ route($item['route']) }}" data-page-nav class="ziifra-employee-shortcut">
                            <span class="ziifra-employee-shortcut-icon">
                                <x-nav-icon :route="$item['route']" />
                            </span>
                            <span>
                                <span class="ziifra-employee-shortcut-label">{{ $item['label'] }}</span>
                                <span class="ziifra-employee-shortcut-hint block mt-0.5">{{ __('employee_dashboard.open_module') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-5 lg:items-stretch">
            @if ($myLeaveBalance)
                <div class="lg:col-span-2">
                    @include('app.dashboard._leave-balance')
                </div>
            @endif

            <section class="ziifra-dashboard-panel {{ $myLeaveBalance ? 'lg:col-span-3' : 'lg:col-span-5' }}">
                <div class="ziifra-dashboard-panel-head">
                    <div>
                        <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('employee_dashboard.my_requests') }}</h3>
                        <p class="text-xs text-ziifra-muted">{{ __('employee_dashboard.my_requests_subtitle') }}</p>
                    </div>
                    <a href="{{ route('leave.index') }}" data-page-nav class="text-xs font-semibold text-ziifra-accent-deep hover:underline">
                        {{ __('employee_dashboard.view_all_leave') }}
                    </a>
                </div>
                <div class="flex min-h-[12rem] flex-1 flex-col p-3 sm:p-4">
                    @if ($myLeaveRequests->isEmpty())
                        <div class="ziifra-dashboard-empty flex-1 py-8 text-center">
                            <p class="text-sm text-ziifra-muted">{{ __('employee_dashboard.my_requests_empty') }}</p>
                            <a href="{{ route('leave.create') }}" data-page-nav class="ziifra-btn-app mt-4 inline-flex">{{ __('employee_dashboard.request_leave') }}</a>
                        </div>
                    @else
                        <ul class="space-y-2">
                            @foreach ($myLeaveRequests as $request)
                                <li>
                                    <a href="{{ route('leave.show', $request) }}" data-page-nav class="ziifra-dashboard-leave-row rounded-xl">
                                        <span class="ziifra-dashboard-avatar">{{ mb_substr($request->leaveType->name, 0, 1) }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-medium text-ziifra-ink">{{ $request->leaveType->name }}</span>
                                            <span class="block text-xs text-ziifra-muted">
                                                {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j') }}
                                                · {{ number_format($request->days, 1) }} {{ __('employee_dashboard.days') }}
                                            </span>
                                        </span>
                                        <span @class([
                                            'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-amber-100 text-amber-900' => $request->status === \App\Enums\LeaveRequestStatus::Pending,
                                            'bg-emerald-50 text-emerald-800' => $request->status === \App\Enums\LeaveRequestStatus::Approved,
                                            'bg-red-50 text-red-800' => $request->status === \App\Enums\LeaveRequestStatus::Rejected,
                                            'bg-ziifra-cream text-ziifra-muted' => ! in_array($request->status, [
                                                \App\Enums\LeaveRequestStatus::Pending,
                                                \App\Enums\LeaveRequestStatus::Approved,
                                                \App\Enums\LeaveRequestStatus::Rejected,
                                            ], true),
                                        ])>{{ $request->status->label() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    @endif
</div>
@endsection
