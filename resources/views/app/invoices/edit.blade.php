@extends('layouts.app')

@section('title', __('invoices.edit'))
@section('header', __('invoices.edit'))

@section('content')
<form method="POST" action="{{ route('invoices.update', $invoice) }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @method('PUT')
    @include('app.invoices._form', ['invoice' => $invoice])
    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('invoices.save') }}</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="ziifra-btn-app-outline">{{ __('common.cancel') }}</a>
    </div>
</form>
@endsection
