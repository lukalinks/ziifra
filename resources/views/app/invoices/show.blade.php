@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('header', $invoice->invoice_number)

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-ziifra-muted">{{ $invoice->client_name }}</p>
                <h2 class="mt-1 text-xl font-semibold text-ziifra-ink">{{ $invoice->title }}</h2>
            </div>
            <span @class([
                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                'bg-red-50 text-red-800' => $invoice->isOverdue(),
                'bg-emerald-50 text-emerald-800' => $invoice->status === \App\Enums\InvoiceStatus::Paid,
                'bg-amber-50 text-amber-900' => $invoice->status === \App\Enums\InvoiceStatus::Draft,
                'bg-sky-50 text-sky-800' => $invoice->status === \App\Enums\InvoiceStatus::Sent,
                'bg-ziifra-cream text-ziifra-muted' => $invoice->status === \App\Enums\InvoiceStatus::Cancelled,
            ])>{{ $invoice->displayStatusLabel() }}</span>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-ziifra-muted">{{ __('invoices.issue_date') }}</dt>
                <dd class="font-medium">{{ $invoice->issue_date->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-ziifra-muted">{{ __('invoices.due_date') }}</dt>
                <dd class="font-medium">{{ $invoice->due_date->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-ziifra-muted">{{ __('invoices.amount') }}</dt>
                <dd class="font-medium">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-ziifra-muted">{{ __('invoices.tax') }}</dt>
                <dd class="font-medium">{{ $invoice->tax_percent }}% ({{ $invoice->currency }} {{ $invoice->taxAmount() }})</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-ziifra-muted">{{ __('invoices.total') }}</dt>
                <dd class="text-lg font-semibold text-ziifra-ink">{{ $invoice->formattedTotal() }}</dd>
            </div>
            @if ($invoice->client_email)
                <div class="sm:col-span-2">
                    <dt class="text-ziifra-muted">{{ __('invoices.client_email') }}</dt>
                    <dd class="font-medium">{{ $invoice->client_email }}</dd>
                </div>
            @endif
            @if ($invoice->notes)
                <div class="sm:col-span-2">
                    <dt class="text-ziifra-muted">{{ __('invoices.notes') }}</dt>
                    <dd class="mt-1">{{ $invoice->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="flex flex-wrap gap-2">
        @can('update', $invoice)
            <a href="{{ route('invoices.edit', $invoice) }}" class="ziifra-btn-app-outline">{{ __('invoices.edit') }}</a>
        @endcan
        @can('markSent', $invoice)
            <form method="POST" action="{{ route('invoices.send', $invoice) }}" data-confirm="{{ __('invoices.confirm_send') }}" data-confirm-accept="{{ __('invoices.send') }}">
                @csrf
                <button type="submit" class="ziifra-btn-primary">{{ __('invoices.send') }}</button>
            </form>
        @endcan
        @can('markPaid', $invoice)
            <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" data-confirm="{{ __('invoices.confirm_paid') }}" data-confirm-accept="{{ __('invoices.mark_paid') }}">
                @csrf
                <button type="submit" class="ziifra-btn-primary">{{ __('invoices.mark_paid') }}</button>
            </form>
        @endcan
        @can('cancel', $invoice)
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" data-confirm="{{ __('invoices.confirm_cancel') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('invoices.cancel_invoice') }}">
                @csrf
                <button type="submit" class="ziifra-btn-app-outline">{{ __('invoices.cancel_invoice') }}</button>
            </form>
        @endcan
        @can('delete', $invoice)
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-confirm="{{ __('invoices.confirm_delete') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('invoices.delete') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('invoices.delete') }}</button>
            </form>
        @endcan
        <a href="{{ route('invoices.index') }}" class="ziifra-btn-app-outline">Back</a>
        @can('view', $invoice)
            <a href="{{ route('invoices.pdf', $invoice) }}" class="ziifra-btn-app-outline">{{ __('invoices.download_pdf') }}</a>
            <a href="{{ route('invoices.export', $invoice) }}" class="ziifra-btn-app-outline">{{ __('invoices.export_excel') }}</a>
        @endcan
    </div>
</div>
@endsection
