@extends('layouts.app')

@section('title', __('settings.invoices.title'))
@section('header', __('settings.invoices.title'))

@section('content')
<p class="mb-6"><a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep">← {{ __('settings.back') }}</a></p>

@php $is = $invoiceSettings; @endphp

<form method="POST" action="{{ route('settings.invoices.update') }}" class="max-w-3xl space-y-6">
    @csrf
    @method('PUT')
    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.invoices.footer_text') }}</label>
            <textarea name="invoice_settings[footer_text]" rows="4" class="mt-1 ziifra-input w-full">{{ old('invoice_settings.footer_text', $is['footer_text']) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.invoices.vat_percent') }}</label>
            <input type="number" step="0.01" name="invoice_settings[vat_percent]" value="{{ old('invoice_settings.vat_percent', $is['vat_percent']) }}" class="mt-1 ziifra-input w-full">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="invoice_settings[vat_manual]" value="1" @checked($is['vat_manual'] ?? false)> {{ __('settings.invoices.vat_manual') }}</label>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.bank_name') }}</label>
            <input name="bank_name" value="{{ old('bank_name', $organization->bank_name) }}" class="mt-1 ziifra-input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">IBAN</label>
            <input name="bank_iban" value="{{ old('bank_iban', $organization->bank_iban) }}" class="mt-1 ziifra-input w-full font-mono text-sm">
        </div>
    </section>
    <button type="submit" class="ziifra-btn-app">{{ __('settings.company.save') }}</button>
</form>
@endsection
