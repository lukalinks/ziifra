@extends('layouts.app')

@section('title', __('invoices.new'))
@section('header', __('invoices.new'))

@section('content')
<form method="POST" action="{{ route('invoices.store') }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @include('app.invoices._form')
    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('invoices.save') }}</button>
        <a href="{{ route('invoices.index') }}" class="ziifra-btn-app-outline">Cancel</a>
    </div>
</form>
@endsection

