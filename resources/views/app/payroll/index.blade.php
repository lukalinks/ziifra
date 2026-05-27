@extends('layouts.app')

@section('title', __('payroll.title'))
@section('header', __('payroll.title'))

@section('content')
@php
    $canCreate = auth()->user()->can('create', \App\Models\PayrollRun::class);
    $runCount = $runs instanceof \Illuminate\Contracts\Pagination\Paginator ? $runs->total() : $runs->count();
@endphp

<div class="ziifra-dashboard-page ziifra-payroll-index">
    <x-mobile.list-toolbar
        :count="__('payroll.count', ['count' => $runCount])"
        :primary-href="$canCreate ? route('payroll.create') : null"
        :primary-label="$canCreate ? __('payroll.new_run') : null">
        <p class="rounded-xl border border-amber-200/70 bg-amber-50/70 px-3 py-2 text-xs leading-relaxed text-amber-950">{{ __('payroll.rules_notice') }}</p>
    </x-mobile.list-toolbar>

    <section class="ziifra-index-toolbar">
        <div class="ziifra-index-toolbar-head">
            <div class="min-w-0">
                <p class="text-sm text-ziifra-muted">{{ __('payroll.subtitle') }}</p>
                <p class="mt-1 text-sm font-medium text-ziifra-ink">{{ __('payroll.count', ['count' => $runCount]) }}</p>
            </div>
            @if ($canCreate)
                <div class="ziifra-index-toolbar-actions">
                    <a href="{{ route('payroll.create') }}" class="ziifra-btn-app !py-2 !text-sm" data-page-nav>{{ __('payroll.new_run') }}</a>
                </div>
            @endif
        </div>

        <div class="ziifra-index-toolbar-body">
            <p class="ziifra-payroll-rules-notice">{{ __('payroll.rules_notice') }}</p>
        </div>
    </section>

    <section class="ziifra-index-panel">
        <div class="ziifra-index-panel-head md:hidden">
            <p class="text-sm font-medium text-ziifra-ink">{{ __('payroll.count', ['count' => $runCount]) }}</p>
        </div>

        @if ($runs->isEmpty())
            <div class="ziifra-dashboard-empty py-12">
                <span class="ziifra-dashboard-empty-icon text-violet-500/70">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                </span>
                <p class="mt-3 font-medium text-ziifra-ink">{{ __('payroll.empty') }}</p>
                @if ($canCreate)
                    <a href="{{ route('payroll.create') }}" class="ziifra-btn-primary mt-4 !text-sm" data-page-nav>{{ __('payroll.new_run') }}</a>
                @endif
            </div>
        @else
            <div class="ziifra-payroll-compact-grid p-3 sm:p-4 md:p-5">
                @foreach ($runs as $run)
                    @include('app.payroll._index-compact-card', ['run' => $run])
                @endforeach
            </div>
            @if ($runs->hasPages())
                <div class="border-t border-ziifra-line/80 px-4 py-3 sm:px-5">
                    {{ $runs->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
