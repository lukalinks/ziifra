@extends('layouts.app')

@section('title', $canRequestOwn && ! $canCreateForOthers ? __('leave.my_title') : __('leave.nav.requests'))
@section('header', $canRequestOwn && ! $canCreateForOthers ? __('leave.my_header') : __('leave.nav.requests'))

@section('content')
@include('app.leave._nav')

@if ($needsProfileLink ?? false)
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        {{ __('leave.needs_profile', ['email' => auth()->user()->email]) }}
    </div>
@endif

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    @if ($canCreateForOthers)
        <form method="GET" action="{{ route('leave.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[10rem] flex-1 sm:flex-none">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="ziifra-input w-full">
                    <option value="">{{ __('leave.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[10rem] flex-1 sm:flex-none">
                <label for="employee_id" class="sr-only">Employee</label>
                <select id="employee_id" name="employee_id" class="ziifra-input w-full">
                    <option value="">All employees</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[10rem] flex-1 sm:flex-none">
                <label for="leave_type_id" class="sr-only">Type</label>
                <select id="leave_type_id" name="leave_type_id" class="ziifra-input w-full">
                    <option value="">All types</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}" @selected(request('leave_type_id') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ziifra-btn-app-outline">{{ __('leave.filter') }}</button>
        </form>
    @else
        <form method="GET" action="{{ route('leave.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[10rem] flex-1 sm:max-w-xs">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="ziifra-input w-full">
                    <option value="">{{ __('leave.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ziifra-btn-app-outline">{{ __('leave.filter') }}</button>
        </form>
    @endif
    @if ($canCreate && ! ($needsProfileLink ?? false))
        <a href="{{ route('leave.create') }}" class="ziifra-btn-primary shrink-0 text-center">
            {{ $canRequestOwn && ! $canCreateForOthers ? __('leave.request_leave') : __('leave.new_request') }}
        </a>
    @endif
</div>

@if ($leaveRequests->isEmpty())
    <div class="rounded-2xl border border-ziifra-line/80 bg-ziifra-paper p-10 text-center">
        <p class="text-sm text-ziifra-muted">
            {{ __('leave.empty') }}
            @if ($canCreate && ! ($needsProfileLink ?? false))
                <a href="{{ route('leave.create') }}" class="font-medium text-ziifra-accent-deep hover:underline">{{ __('leave.submit_request') }}</a>.
            @endif
        </p>
    </div>
@else
    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @foreach ($leaveRequests as $request)
            <a href="{{ route('leave.show', $request) }}" data-page-nav class="ziifra-leave-card block">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        @if ($canCreateForOthers)
                            <p class="text-xs font-medium text-ziifra-muted">{{ $request->employee->fullName() }}</p>
                        @endif
                        <p class="font-semibold text-ziifra-ink">{{ $request->leaveType->name }}</p>
                        <p class="mt-1 text-sm text-ziifra-muted">
                            {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j, Y') }}
                        </p>
                    </div>
                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-emerald-50 text-emerald-800' => $request->status === \App\Enums\LeaveRequestStatus::Approved,
                        'bg-amber-50 text-amber-800' => $request->status === \App\Enums\LeaveRequestStatus::Pending,
                        'bg-red-50 text-red-800' => $request->status === \App\Enums\LeaveRequestStatus::Rejected,
                        'bg-ziifra-cream text-ziifra-muted' => ! in_array($request->status, [
                            \App\Enums\LeaveRequestStatus::Approved,
                            \App\Enums\LeaveRequestStatus::Pending,
                            \App\Enums\LeaveRequestStatus::Rejected,
                        ], true),
                    ])>{{ $request->status->label() }}</span>
                </div>
                <p class="mt-3 text-xs text-ziifra-muted">{{ number_format($request->days, 1) }} {{ __('leave.days') }}</p>
            </a>
        @endforeach
        <div class="pt-2">{{ $leaveRequests->links() }}</div>
    </div>

    {{-- Desktop table --}}
    <div class="hidden overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper md:block">
        <table class="min-w-full divide-y divide-ziifra-line/80 text-sm">
            <thead class="bg-ziifra-cream">
                <tr>
                    @if ($canCreateForOthers)
                        <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Employee</th>
                    @endif
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Dates</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Days</th>
                    <th class="px-4 py-3 text-left font-medium text-ziifra-muted">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-ziifra-muted"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($leaveRequests as $request)
                    <tr class="hover:bg-ziifra-cream/30">
                        @if ($canCreateForOthers)
                            <td class="px-4 py-3 font-medium text-ziifra-ink">{{ $request->employee->fullName() }}</td>
                        @endif
                        <td class="px-4 py-3 text-ziifra-muted">{{ $request->leaveType->name }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">
                            {{ $request->start_date->format('M j') }} – {{ $request->end_date->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ number_format($request->days, 1) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-50 text-green-700' => $request->status === \App\Enums\LeaveRequestStatus::Approved,
                                'bg-amber-50 text-amber-700' => $request->status === \App\Enums\LeaveRequestStatus::Pending,
                                'bg-red-50 text-red-700' => $request->status === \App\Enums\LeaveRequestStatus::Rejected,
                                'bg-ziifra-cream text-ziifra-muted' => ! in_array($request->status, [
                                    \App\Enums\LeaveRequestStatus::Approved,
                                    \App\Enums\LeaveRequestStatus::Pending,
                                    \App\Enums\LeaveRequestStatus::Rejected,
                                ], true),
                            ])>{{ $request->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('leave.show', $request) }}" class="font-medium text-ziifra-accent-deep hover:underline">{{ __('leave.view') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-ziifra-line/80 px-4 py-3">
            {{ $leaveRequests->links() }}
        </div>
    </div>
@endif
@endsection
