@extends('admin.layout')

@section('title', __('admin.nav.audit_log'))

@section('content')
<div>
    <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.audit.heading') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('admin.audit.subtitle') }}</p>
</div>

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <select name="action" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ __('admin.audit.filter_action') }}</option>
        @foreach ($actions as $action)
            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $platform->actionLabel($action) }}</option>
        @endforeach
    </select>
    <select name="admin_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ __('admin.audit.filter_admin') }}</option>
        @foreach ($admins as $admin)
            <option value="{{ $admin->id }}" @selected((int) request('admin_id') === $admin->id)>{{ $admin->email }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
</form>

<div class="mt-6">
    @include('admin.partials.audit-table', ['logs' => $logs, 'platform' => $platform])
</div>

<div class="mt-4">
    {{ $logs->links() }}
</div>
@endsection
