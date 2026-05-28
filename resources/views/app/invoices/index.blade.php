@extends('layouts.app')

@section('title', __('invoices.title'))
@section('header', __('invoices.title'))

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('status');
    $activeFilterCount = collect([request('search'), request('status')])->filter(fn ($v) => filled($v))->count();
    $canCreate = auth()->user()->can('create', \App\Models\Invoice::class);
@endphp

<div class="mb-4 hidden items-center justify-between gap-3 md:flex">
    <p class="text-sm text-ziifra-muted">{{ __('invoices.subtitle') }}</p>
    @can('update', $organization)
        <a href="{{ route('settings.invoices.edit') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-ziifra-line/80 text-ziifra-muted hover:text-ziifra-ink" title="{{ __('invoices.settings') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
    @endcan
</div>

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
