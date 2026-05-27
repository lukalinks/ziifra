@extends('layouts.app')

@section('title', __('invoices.title'))
@section('header', __('invoices.title'))

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('status');
    $activeFilterCount = collect([request('search'), request('status')])->filter(fn ($v) => filled($v))->count();
    $canCreate = auth()->user()->can('create', \App\Models\Invoice::class);
@endphp

<p class="mb-4 hidden text-sm text-ziifra-muted md:block">{{ __('invoices.subtitle') }}</p>

<x-mobile.list-toolbar
    :count="__('invoices.count', ['count' => $invoices->total()])"
    :primary-href="$canCreate ? route('invoices.create') : null"
    :primary-label="$canCreate ? __('invoices.new') : null">
    <x-mobile.filter-form
        :action="route('invoices.index')"
        search-id="invoices-search-mobile"
        :search-placeholder="__('invoices.search_placeholder')"
        :search-value="request('search', '')"
        :clear-href="route('invoices.index')"
        :active-filter-count="$activeFilterCount"
        :has-filters="$hasFilters">
        <x-slot:filters>
            <div>
                <label for="status-mobile" class="ziifra-label-field">{{ __('common.status') }}</label>
                <select id="status-mobile" name="status" class="ziifra-input">
                    <option value="">{{ __('invoices.all_statuses') }}</option>
                    <option value="overdue" @selected(request('status') === 'overdue')>{{ __('invoices.status_overdue') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:filters>
    </x-mobile.filter-form>
</x-mobile.list-toolbar>

<div class="mt-6 ziifra-page-toolbar hidden md:flex">
    <form method="GET" action="{{ route('invoices.index') }}" class="ziifra-filter-form">
        <div>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('invoices.search_placeholder') }}"
                class="min-w-[12rem] rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        </div>
        <div>
            <select name="status" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">{{ __('invoices.all_statuses') }}</option>
                <option value="overdue" @selected(request('status') === 'overdue')>{{ __('invoices.status_overdue') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium hover:bg-ziifra-cream">{{ __('invoices.filter') }}</button>
    </form>
    @can('create', \App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="ziifra-btn-primary shrink-0 text-center">{{ __('invoices.new') }}</a>
    @endcan
</div>

<div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    @if ($invoices->isEmpty())
        <p class="p-8 text-center text-sm text-ziifra-muted">{{ __('invoices.empty') }}</p>
    @else
        <div class="ziifra-mobile-list-cards">
            @foreach ($invoices as $invoice)
                @php $overdue = $invoice->isOverdue(); @endphp
                <x-mobile.list-card :href="route('invoices.show', $invoice)" :avatar="mb_strtoupper(mb_substr($invoice->client_name, 0, 1))">
                    <span class="block truncate font-semibold text-ziifra-ink">{{ $invoice->invoice_number }}</span>
                    <span class="mt-0.5 block truncate text-sm text-ziifra-muted">{{ $invoice->client_name }}</span>
                    @if ($invoice->title)
                        <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $invoice->title }}</span>
                    @endif
                    <span class="ziifra-list-card-meta">
                        <span class="ziifra-list-card-tag">{{ $invoice->formattedTotal() }}</span>
                        <span class="ziifra-list-card-tag">{{ $invoice->due_date->format('M j, Y') }}</span>
                    </span>
                    <span class="ziifra-list-card-badges">
                        <span @class([
                            'ziifra-list-badge',
                            'ziifra-list-badge-danger' => $overdue,
                            'ziifra-list-badge-success' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Paid,
                            'ziifra-list-badge-warning' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Draft,
                            'ziifra-list-badge-muted' => ! $overdue && ! in_array($invoice->status, [\App\Enums\InvoiceStatus::Paid, \App\Enums\InvoiceStatus::Draft], true),
                        ])>{{ $invoice->displayStatusLabel() }}</span>
                    </span>
                </x-mobile.list-card>
            @endforeach
        </div>
        @if ($invoices->hasPages())
            <div class="border-t border-ziifra-line/80 px-4 py-3 md:hidden">{{ $invoices->links() }}</div>
        @endif

        <div class="ziifra-table-scroll hidden md:block">
        <table class="min-w-full divide-y divide-ziifra-line/60 text-sm">
            <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('invoices.invoice_number') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.client') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.total') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.due_date') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.all_statuses') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($invoices as $invoice)
                    <tr class="hover:bg-ziifra-cream/30">
                        <td class="px-4 py-3 font-medium text-ziifra-ink">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $invoice->client_name }}</p>
                            <p class="text-xs text-ziifra-muted">{{ $invoice->title }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->formattedTotal() }}</td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $invoice->due_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            @php $overdue = $invoice->isOverdue(); @endphp
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-red-50 text-red-800' => $overdue,
                                'bg-emerald-50 text-emerald-800' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Paid,
                                'bg-amber-50 text-amber-900' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Draft,
                                'bg-sky-50 text-sky-800' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Sent,
                                'bg-ziifra-cream text-ziifra-muted' => ! $overdue && $invoice->status === \App\Enums\InvoiceStatus::Cancelled,
                            ])>{{ $invoice->displayStatusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-ziifra-accent-deep hover:underline">{{ __('invoices.view') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @if ($invoices->hasPages())
            <div class="hidden border-t border-ziifra-line/60 px-4 py-3 md:block">{{ $invoices->links() }}</div>
        @endif
    @endif
</div>
@endsection
