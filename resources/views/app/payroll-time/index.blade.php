@extends('layouts.app')

@section('title', __('payroll_time.title'))
@section('header', __('payroll_time.title'))

@section('content')
@php
    $exportMonthParams = ['year' => $year, 'month' => $month, 'project_id' => request('project_id')];
    $exportYearParams = ['year' => $year, 'project_id' => request('project_id')];
    $archiveMonthParams = array_merge($exportMonthParams, ['archive' => 1]);
@endphp

<div class="ziifra-dashboard-page" data-payroll-time
    data-upsert-url="{{ route('payroll-time.hours.upsert') }}"
    data-rate-url-template="{{ route('payroll-time.rate.update', ['employee' => '__EMPLOYEE__']) }}"
    data-project-id="{{ $grid['project']?->id }}"
    data-csrf="{{ csrf_token() }}">

    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-ziifra-muted">{{ __('payroll_time.subtitle') }}</p>
            @if ($canManage && ! ($grid['any_hours_editable'] ?? $grid['editable']))
                <p class="mt-1 text-xs text-amber-700">{{ __('payroll_time.select_project_to_edit') }}</p>
            @endif
            @if (($grid['totals']['pending_hours'] ?? 0) > 0 && ($canApprove ?? $canManage))
                <p class="mt-1 text-xs text-amber-700">{{ __('payroll_time.pending_approval_banner', ['hours' => number_format($grid['totals']['pending_hours'], 1)]) }}</p>
            @endif
            @if ($linkedEmployee && ! $canManage)
                <p class="mt-1 text-xs text-ziifra-muted">{{ __('payroll_time.employee_submit_hint') }}</p>
            @endif
        </div>
        <a href="{{ route('settings.payroll.edit') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-ziifra-line/80 text-ziifra-muted hover:text-ziifra-ink" title="{{ __('payroll_time.settings') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-ziifra-muted">{{ __('payroll_time.download_all') }}:</span>
        <a href="{{ route('payroll-time.export.pdf', $exportMonthParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.pdf_month') }}</a>
        <a href="{{ route('payroll-time.export.excel', $exportMonthParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.excel_month') }}</a>
        @if ($canManage)
            <span class="mx-1 text-ziifra-line">|</span>
            <a href="{{ route('payroll-time.export.pdf', $archiveMonthParams) }}" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.save_to_documents') }}</a>
            <form method="POST" action="{{ route('payroll-time.archive.past', $organization) }}" class="inline">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                <button type="submit" class="ziifra-btn-app-outline !text-sm">{{ __('payroll_time.archive_past_months') }}</button>
            </form>
            @if ($payrollFolder)
                <a href="{{ route('documents.index', ['organization' => $organization, 'folder' => $payrollFolder->id]) }}" class="text-xs text-ziifra-accent-deep hover:underline" data-page-nav>{{ __('payroll_time.open_payroll_folder') }}</a>
            @endif
        @endif
    </div>

    @include('app.payroll-time._grid')

    <p class="mt-3 text-xs text-ziifra-muted">
        @if ($canManage)
            {{ __('payroll_time.hours_edit_hint') }}
        @else
            {{ __('payroll_time.hours_employee_hint') }}
        @endif
    </p>
</div>

@push('scripts')
    @vite('resources/js/payroll-time-grid.js')
@endpush
@endsection
