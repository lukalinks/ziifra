@extends('layouts.app')

@section('title', $claim->title)
@section('header', __('expenses.title'))

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-ziifra-muted">{{ $claim->employee->fullName() }}</p>
                <h2 class="mt-1 text-xl font-semibold text-ziifra-ink">{{ $claim->title }}</h2>
                <p class="mt-1 text-sm text-ziifra-muted">{{ $claim->category->label() }}</p>
            </div>
            <span @class([
                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                'bg-amber-50 text-amber-900' => $claim->status === \App\Enums\ExpenseClaimStatus::Pending,
                'bg-emerald-50 text-emerald-800' => $claim->status === \App\Enums\ExpenseClaimStatus::Approved,
                'bg-red-50 text-red-800' => $claim->status === \App\Enums\ExpenseClaimStatus::Rejected,
                'bg-ziifra-cream text-ziifra-muted' => $claim->status === \App\Enums\ExpenseClaimStatus::Cancelled,
            ])>{{ $claim->status->label() }}</span>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-ziifra-muted">{{ __('expenses.amount') }}</dt>
                <dd class="text-lg font-semibold">{{ $claim->formattedAmount() }}</dd>
            </div>
            <div>
                <dt class="text-ziifra-muted">{{ __('expenses.expense_date') }}</dt>
                <dd class="font-medium">{{ $claim->expense_date->format('M j, Y') }}</dd>
            </div>
            @if ($claim->notes)
                <div class="sm:col-span-2">
                    <dt class="text-ziifra-muted">{{ __('expenses.notes') }}</dt>
                    <dd class="mt-1">{{ $claim->notes }}</dd>
                </div>
            @endif
            @if ($claim->rejection_reason)
                <div class="sm:col-span-2">
                    <dt class="text-ziifra-muted">{{ __('expenses.rejection_reason') }}</dt>
                    <dd class="mt-1 text-red-700">{{ $claim->rejection_reason }}</dd>
                </div>
            @endif
        </dl>

        @if ($claim->hasReceipt())
            <p class="mt-4">
                <a href="{{ route('expenses.receipt', $claim) }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                    {{ __('expenses.download_receipt') }}
                </a>
            </p>
        @endif
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($canApprove && $claim->isPending())
            <form method="POST" action="{{ route('expenses.approve', $claim) }}"
                data-confirm="{{ __('expenses.confirm_approve') }}"
                data-confirm-accept="{{ __('expenses.approve') }}">
                @csrf
                <button type="submit" class="ziifra-btn-primary">{{ __('expenses.approve') }}</button>
            </form>
            <form method="POST" action="{{ route('expenses.reject', $claim) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <input type="text" name="rejection_reason" required maxlength="1000" placeholder="{{ __('expenses.rejection_reason') }}"
                    class="min-w-[16rem] rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <button type="submit" class="ziifra-btn-app-outline">{{ __('expenses.reject') }}</button>
            </form>
        @endif
        @can('cancel', $claim)
            @if ($claim->isPending())
                <form method="POST" action="{{ route('expenses.cancel', $claim) }}"
                    data-confirm="{{ __('expenses.confirm_cancel') }}"
                    data-confirm-variant="danger"
                    data-confirm-accept="{{ __('expenses.cancel') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('expenses.cancel') }}</button>
                </form>
            @endif
        @endcan
        <a href="{{ route('expenses.index') }}" class="ziifra-btn-app-outline">Back</a>
    </div>
</div>
@endsection
