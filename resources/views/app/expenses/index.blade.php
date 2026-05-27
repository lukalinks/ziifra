@extends('layouts.app')

@section('title', __('expenses.title'))
@section('header', __('expenses.title'))

@section('content')
@php
    $hasFilters = request()->filled('status') || request()->filled('employee_id');
    $activeFilterCount = collect([request('status'), request('employee_id')])->filter(fn ($v) => filled($v))->count();
@endphp

<p class="mb-4 hidden text-sm text-ziifra-muted md:block">{{ __('expenses.subtitle') }}</p>

@if ($needsProfileLink ?? false)
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 md:mt-4">
        {{ __('expenses.needs_profile') }}
    </div>
@endif

<x-mobile.list-toolbar
    :count="__('expenses.count', ['count' => $claims->total()])"
    :primary-href="($canCreate && ! ($needsProfileLink ?? false)) ? route('expenses.create') : null"
    :primary-label="($canCreate && ! ($needsProfileLink ?? false)) ? __('expenses.new') : null">
    <x-mobile.filter-form
        :action="route('expenses.index')"
        :show-search="false"
        search-id="expenses-search-mobile"
        :clear-href="route('expenses.index')"
        :active-filter-count="$activeFilterCount"
        :has-filters="$hasFilters">
        <x-slot:filters>
            <div>
                <label for="status-mobile" class="ziifra-label-field">{{ __('common.status') }}</label>
                <select id="status-mobile" name="status" class="ziifra-input">
                    <option value="">{{ __('expenses.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            @if ($employees->isNotEmpty())
                <div>
                    <label for="employee_id-mobile" class="ziifra-label-field">{{ __('expenses.employee') }}</label>
                    <select id="employee_id-mobile" name="employee_id" class="ziifra-input">
                        <option value="">{{ __('expenses.all_employees') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </x-slot:filters>
    </x-mobile.filter-form>
</x-mobile.list-toolbar>

<div class="mt-6 mb-6 hidden flex-col gap-4 sm:flex-row sm:items-end sm:justify-between md:flex">
    <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-end gap-3">
        <select name="status" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            <option value="">{{ __('expenses.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @if ($employees->isNotEmpty())
            <select name="employee_id" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">{{ __('expenses.all_employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->fullName() }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium hover:bg-ziifra-cream">{{ __('expenses.filter') }}</button>
    </form>
    @if ($canCreate && ! ($needsProfileLink ?? false))
        <a href="{{ route('expenses.create') }}" class="ziifra-btn-primary shrink-0 text-center">{{ __('expenses.new') }}</a>
    @endif
</div>

<div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    @if ($claims->isEmpty())
        <p class="p-8 text-center text-sm text-ziifra-muted">{{ __('expenses.empty') }}</p>
    @else
        <div class="ziifra-mobile-list-cards">
            @foreach ($claims as $claim)
                <x-mobile.list-card :href="route('expenses.show', $claim)" :avatar="mb_strtoupper(mb_substr($claim->employee->fullName(), 0, 1))">
                    @if ($employees->isNotEmpty())
                        <span class="text-xs font-medium text-ziifra-muted">{{ $claim->employee->fullName() }}</span>
                    @endif
                    <span class="block truncate font-semibold text-ziifra-ink">{{ $claim->title }}</span>
                    <span class="mt-0.5 block text-sm text-ziifra-muted">{{ $claim->category->label() }}</span>
                    <span class="ziifra-list-card-meta">
                        <span class="ziifra-list-card-tag">{{ $claim->formattedAmount() }}</span>
                        <span class="ziifra-list-card-tag">{{ $claim->expense_date->format('M j, Y') }}</span>
                    </span>
                    <span class="ziifra-list-card-badges">
                        <span @class([
                            'ziifra-list-badge',
                            'ziifra-list-badge-warning' => $claim->status === \App\Enums\ExpenseClaimStatus::Pending,
                            'ziifra-list-badge-success' => $claim->status === \App\Enums\ExpenseClaimStatus::Approved,
                            'ziifra-list-badge-danger' => $claim->status === \App\Enums\ExpenseClaimStatus::Rejected,
                            'ziifra-list-badge-muted' => $claim->status === \App\Enums\ExpenseClaimStatus::Cancelled,
                        ])>{{ $claim->status->label() }}</span>
                    </span>
                </x-mobile.list-card>
            @endforeach
        </div>
        @if ($claims->hasPages())
            <div class="border-t border-ziifra-line/60 px-4 py-3 md:hidden">{{ $claims->links() }}</div>
        @endif

        <div class="ziifra-table-scroll hidden md:block">
        <table class="min-w-full divide-y divide-ziifra-line/60 text-sm">
            <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('expenses.employee') }}</th>
                    <th class="px-4 py-3">{{ __('expenses.description') }}</th>
                    <th class="px-4 py-3">{{ __('expenses.amount') }}</th>
                    <th class="px-4 py-3">{{ __('expenses.expense_date') }}</th>
                    <th class="px-4 py-3">{{ __('common.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($claims as $claim)
                    <tr class="hover:bg-ziifra-cream/30">
                        <td class="px-4 py-3 font-medium">{{ $claim->employee->fullName() }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $claim->title }}</p>
                            <p class="text-xs text-ziifra-muted">{{ $claim->category->label() }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $claim->formattedAmount() }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $claim->expense_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-amber-50 text-amber-900' => $claim->status === \App\Enums\ExpenseClaimStatus::Pending,
                                'bg-emerald-50 text-emerald-800' => $claim->status === \App\Enums\ExpenseClaimStatus::Approved,
                                'bg-red-50 text-red-800' => $claim->status === \App\Enums\ExpenseClaimStatus::Rejected,
                                'bg-ziifra-cream text-ziifra-muted' => $claim->status === \App\Enums\ExpenseClaimStatus::Cancelled,
                            ])>{{ $claim->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('expenses.show', $claim) }}" data-page-nav class="font-medium text-ziifra-accent-deep hover:underline">{{ __('common.view') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @if ($claims->hasPages())
            <div class="hidden border-t border-ziifra-line/60 px-4 py-3 md:block">{{ $claims->links() }}</div>
        @endif
    @endif
</div>
@endsection
