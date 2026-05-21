@extends('admin.layout')

@section('title', __('admin.nav.dashboard'))

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.dashboard.heading') }}</h1>
<p class="mt-1 text-sm text-slate-600">{{ __('admin.dashboard.subtitle') }}</p>

<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">Organizations</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $organizationCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">Users</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $userCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">On trial</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $trialCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">{{ __('admin.dashboard.trial_expiring') }}</p>
        <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $trialExpiringCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">{{ __('admin.dashboard.paid_workspaces') }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $paidWorkspaceCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-5">
        <p class="text-sm text-slate-500">Suspended</p>
        <p class="mt-2 text-2xl font-semibold text-red-700">{{ $suspendedCount }}</p>
    </div>
</div>

<div class="mt-10 grid gap-8 lg:grid-cols-2">
    <section>
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('admin.dashboard.recent_organizations') }}</h2>
            <a href="{{ route('admin.organizations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all</a>
        </div>
        <ul class="mt-4 space-y-2">
            @foreach ($recentOrganizations as $organization)
                <li class="flex items-center justify-between rounded-lg border border-slate-200 bg-ziifra-paper px-4 py-3 text-sm">
                    <div>
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                            {{ $organization->name }}
                        </a>
                        <p class="text-xs text-slate-500">{{ $organization->slug }}</p>
                    </div>
                    <span class="text-slate-500">{{ $organization->created_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section>
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('admin.dashboard.recent_activity') }}</h2>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">{{ __('admin.dashboard.view_all_activity') }}</a>
        </div>
        <ul class="mt-4 space-y-2 text-sm">
            @foreach ($recentAuditLogs as $log)
                <li class="rounded-lg border border-slate-200 bg-ziifra-paper px-4 py-3">
                    <span class="font-medium text-slate-900">{{ $platform->actionLabel($log->action) }}</span>
                    <span class="text-slate-500">— {{ $log->admin?->email }}</span>
                    @if ($log->organization)
                        <span class="text-slate-400">· {{ $log->organization->name }}</span>
                    @endif
                    <span class="block text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
