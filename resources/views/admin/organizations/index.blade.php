@extends('admin.layout')

@section('title', __('admin.nav.organizations'))

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.organizations.heading') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('admin.organizations.subtitle') }}</p>
    </div>
</div>

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.organizations.search_placeholder') }}"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ __('admin.organizations.filter_all') }}</option>
        <option value="suspended" @selected(request('status') === 'suspended')>{{ __('admin.organizations.filter_suspended') }}</option>
        <option value="trial_expired" @selected(request('status') === 'trial_expired')>{{ __('admin.organizations.filter_trial_expired') }}</option>
    </select>
    <select name="plan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ __('admin.organizations.filter_plan_all') }}</option>
        @foreach ($plans as $plan)
            <option value="{{ $plan->value }}" @selected(request('plan') === $plan->value)>{{ $plan->label() }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
</form>

<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-ziifra-paper">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-3 font-medium">Company</th>
                <th class="px-4 py-3 font-medium">Plan</th>
                <th class="px-4 py-3 font-medium">Employees</th>
                <th class="px-4 py-3 font-medium">Trial ends</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($organizations as $organization)
                @php($plan = $billing->plan($organization))
                @php($status = $platform->organizationStatusLabel($organization))
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $organization->name }}</p>
                        <p class="text-xs text-slate-500">{{ $organization->slug }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $plan->label() }}</td>
                    <td class="px-4 py-3">{{ $organization->active_employees_count }}</td>
                    <td class="px-4 py-3">
                        {{ $billing->trialEndsAt($organization)?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($status === 'suspended')
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Suspended</span>
                        @elseif ($status === 'trial_expired')
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Trial expired</span>
                        @else
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Manage</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $organizations->links() }}
</div>
@endsection
