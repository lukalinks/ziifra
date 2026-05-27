@extends('layouts.app')

@section('title', __('employees.edit'))
@section('header', __('employees.edit'))

@section('content')
<div class="ziifra-dashboard-page mx-auto max-w-2xl">
    <a href="{{ route('employees.show', $employee) }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $employee->fullName() }}
    </a>

    <div class="mt-4 rounded-2xl border border-ziifra-line/80 bg-ziifra-paper p-6 sm:p-8">
        <h1 class="text-xl font-semibold text-ziifra-ink">{{ __('employees.edit') }}</h1>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('employees.edit_subtitle') }}</p>

        <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @method('PUT')
            @include('app.employees._form', ['employee' => $employee])
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="ziifra-btn-primary w-full justify-center sm:w-auto">
                    {{ __('employees.save_changes') }}
                </button>
                <a href="{{ route('employees.show', $employee) }}" class="ziifra-btn-app-outline w-full justify-center sm:w-auto" data-page-nav>
                    {{ __('common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
