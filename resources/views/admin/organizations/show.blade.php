@extends('admin.layout')

@section('title', $organization->name)

@section('content')
<a href="{{ route('admin.organizations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ __('admin.nav.organizations') }}</a>

<h1 class="mt-4 text-2xl font-semibold text-slate-900">{{ $organization->name }}</h1>
<p class="text-sm text-slate-600">
    {{ $organization->slug }}
    · Owner: {{ $organization->owner?->email ?? '—' }}
    · {{ __('admin.organizations.created') }}: {{ $organization->created_at->format('d M Y') }}
</p>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-6">
        <h2 class="font-semibold text-slate-900">Subscription</h2>
        <p class="mt-2 text-sm text-slate-600">
            Plan: <strong>{{ $billing->plan($organization)->label() }}</strong><br>
            Employees: {{ $employeeCount }}
            @if ($limit = $billing->employeeLimit($organization))
                / {{ $limit }}
            @endif
            <br>
            Trial ends: {{ $billing->trialEndsAt($organization)?->format('d M Y H:i') ?? '—' }}<br>
            @if ($organization->stripe_id)
                Stripe customer: <code class="text-xs">{{ $organization->stripe_id }}</code><br>
            @endif
            @if ($organization->stripe_subscription_status)
                Stripe subscription: <strong>{{ $organization->stripe_subscription_status }}</strong>
                @if ($organization->stripe_subscription_ends_at)
                    (ends {{ $organization->stripe_subscription_ends_at->format('d M Y') }})
                @endif
            @endif
        </p>

        <form method="POST" action="{{ route('admin.organizations.plan', $organization) }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            @method('PUT')
            <div>
                <label for="plan" class="block text-xs font-medium text-slate-500">Change plan</label>
                <select id="plan" name="plan" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->value }}" @selected($billing->plan($organization) === $plan)>{{ $plan->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update plan</button>
        </form>

        <form method="POST" action="{{ route('admin.organizations.trial', $organization) }}" class="mt-6 border-t border-slate-100 pt-6">
            @csrf
            @method('PUT')
            <h3 class="text-sm font-semibold text-slate-900">{{ __('admin.organizations.extend_trial') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.organizations.extend_trial_help') }}</p>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div>
                    <label for="trial_ends_at" class="block text-xs font-medium text-slate-500">{{ __('admin.organizations.trial_ends_at') }}</label>
                    <input type="date" id="trial_ends_at" name="trial_ends_at" required
                        value="{{ $billing->trialEndsAt($organization)?->format('Y-m-d') ?? now()->addDays(14)->format('Y-m-d') }}"
                        min="{{ now()->addDay()->format('Y-m-d') }}"
                        class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                    {{ __('admin.organizations.extend_trial') }}
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-ziifra-paper p-6">
        <h2 class="font-semibold text-slate-900">Actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.organizations.impersonate', $organization) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100">
                    {{ __('admin.impersonate_owner') }}
                </button>
            </form>
            @if ($organization->suspended_at)
                <form method="POST" action="{{ route('admin.organizations.unsuspend', $organization) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-800">
                        Reactivate
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.organizations.suspend', $organization) }}"
                    data-confirm="Suspend this organization? Members will lose access."
                    data-confirm-variant="danger"
                    data-confirm-accept="Suspend">
                    @csrf
                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-800">
                        Suspend
                    </button>
                </form>
            @endif
        </div>
        <p class="mt-4 text-xs text-slate-500">
            While impersonating, suspended and expired-trial restrictions are bypassed for support.
        </p>
    </div>
</div>

<section class="mt-10">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('admin.organizations.members') }}</h2>
        <a href="{{ route('admin.audit-logs.index', ['organization_id' => $organization->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
            {{ __('admin.dashboard.view_all_activity') }}
        </a>
    </div>
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-ziifra-paper">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">User</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($organization->users as $member)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $member) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                {{ $member->name }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $member->email }}</p>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $member->pivot->role }}</td>
                        <td class="px-4 py-3 text-right">
                            @if (! $member->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.users.impersonate', $member) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                                    <button type="submit" class="font-medium text-indigo-600 hover:text-indigo-700">
                                        {{ __('admin.impersonate') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@if ($auditLogs->isNotEmpty())
    <h2 class="mt-10 text-lg font-semibold text-slate-900">Recent admin activity</h2>
    <div class="mt-4">
        @include('admin.partials.audit-table', ['logs' => $auditLogs, 'platform' => $platform])
    </div>
@endif
@endsection
