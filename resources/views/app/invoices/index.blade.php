@extends('layouts.app')

@section('title', __('invoices.title'))
@section('header', __('invoices.title'))

@section('content')
<p class="text-sm text-ziifra-muted">{{ __('invoices.subtitle') }}</p>

<div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <form method="GET" action="{{ route('invoices.index') }}" class="flex flex-wrap items-end gap-3">
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
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-ziifra-accent-deep hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($invoices->hasPages())
            <div class="border-t border-ziifra-line/60 px-4 py-3">{{ $invoices->links() }}</div>
        @endif
    @endif
</div>
@endsection
