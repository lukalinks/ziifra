@extends('layouts.app')

@section('title', __('leave.show.title'))
@section('header', __('leave.show.title'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-ziifra-muted">{{ $leaveRequest->leaveType->name }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-ziifra-ink">{{ $leaveRequest->employee->fullName() }}</h2>
            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                @if($leaveRequest->status === \App\Enums\LeaveRequestStatus::Approved) bg-green-50 text-green-700
                @elseif($leaveRequest->status === \App\Enums\LeaveRequestStatus::Pending) bg-amber-50 text-amber-700
                @elseif($leaveRequest->status === \App\Enums\LeaveRequestStatus::Rejected) bg-red-50 text-red-700
                @else bg-ziifra-cream text-ziifra-muted @endif">
                {{ $leaveRequest->status->label() }}
            </span>
        </div>
        <a href="{{ route('leave.index') }}" data-page-nav class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('leave.show.back') }}</a>
    </div>

    <dl class="mt-8 grid gap-6 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 sm:grid-cols-2">
        <div>
            <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.dates') }}</dt>
            <dd class="mt-1 text-ziifra-ink">
                {{ $leaveRequest->start_date->format('M j, Y') }} – {{ $leaveRequest->end_date->format('M j, Y') }}
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.working_days') }}</dt>
            <dd class="mt-1 text-ziifra-ink">{{ number_format($leaveRequest->days, 1) }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.balance', ['year' => $balance->year]) }}</dt>
            <dd class="mt-1 text-ziifra-ink">
                {{ __('leave.show.remaining', ['count' => number_format($balance->remainingDays(), 1)]) }}
                <span class="text-ziifra-muted">{{ __('leave.show.used_of', ['used' => number_format($balance->used_days, 1), 'total' => number_format($balance->entitled_days, 1)]) }}</span>
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.submitted_by') }}</dt>
            <dd class="mt-1 text-ziifra-ink">{{ $leaveRequest->submittedBy->name }}</dd>
        </div>
        @if ($leaveRequest->reviewedBy)
            <div>
                <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.reviewed_by') }}</dt>
                <dd class="mt-1 text-ziifra-ink">
                    {{ $leaveRequest->reviewedBy->name }}
                    @if ($leaveRequest->reviewed_at)
                        <span class="text-ziifra-muted">· {{ $leaveRequest->reviewed_at->format('M j, Y g:i A') }}</span>
                    @endif
                </dd>
            </div>
        @endif
        @if ($leaveRequest->rejection_reason)
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.rejection_reason') }}</dt>
                <dd class="mt-1 text-ziifra-ink">{{ $leaveRequest->rejection_reason }}</dd>
            </div>
        @endif
        @if ($leaveRequest->notes)
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-ziifra-muted">{{ __('leave.show.notes') }}</dt>
                <dd class="mt-1 text-ziifra-ink">{{ $leaveRequest->notes }}</dd>
            </div>
        @endif
    </dl>

    @if ($leaveRequest->isPending())
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($canApprove)
                <form method="POST" action="{{ route('leave.approve', $leaveRequest) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                        {{ __('leave.show.approve') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('leave.reject', $leaveRequest) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="sr-only">{{ __('leave.show.rejection_reason') }}</label>
                        <input id="rejection_reason" name="rejection_reason" type="text" placeholder="{{ __('leave.show.reject_reason_placeholder') }}"
                            value="{{ old('rejection_reason') }}"
                            class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                        {{ __('leave.show.reject') }}
                    </button>
                </form>
            @endif
            @if ($canCancel)
                <form method="POST" action="{{ route('leave.cancel', $leaveRequest) }}" data-confirm="{{ __('leave.confirm_cancel') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('leave.cancel') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
                        {{ __('leave.show.cancel_request') }}
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
