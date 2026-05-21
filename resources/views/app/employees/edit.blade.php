@extends('layouts.app')

@section('title', 'Edit employee')
@section('header', 'Edit employee')

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-8">
    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('app.employees._form', ['employee' => $employee])
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                Save changes
            </button>
            <a href="{{ route('employees.show', $employee) }}" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
