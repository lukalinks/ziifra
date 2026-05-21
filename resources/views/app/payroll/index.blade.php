@extends('layouts.app')

@section('title', __('payroll.title'))
@section('header', __('payroll.title'))

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-ziifra-muted">{{ __('payroll.rules_notice') }}</p>
    @can('create', \App\Models\PayrollRun::class)
        <a href="{{ route('payroll.create') }}" class="ziifra-btn-primary shrink-0 text-center">
            {{ __('payroll.new_run') }}
        </a>
    @endcan
</div>

@if ($runs->isEmpty())
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-8 text-center text-sm text-ziifra-muted">
        {{ __('payroll.empty') }}
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
        <table class="min-w-full divide-y divide-ziifra-line/80 text-sm">
            <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('payroll.period') }}</th>
                    <th class="px-4 py-3">{{ __('payroll.status') }}</th>
                    <th class="px-4 py-3">{{ __('payroll.employees') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($runs as $run)
                    <tr>
                        <td class="px-4 py-3 font-medium text-ziifra-ink">{{ $run->periodLabel() }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $run->isLocked() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">
                                {{ $run->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ziifra-muted">{{ $run->items_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ $run->showUrl() }}" class="font-medium text-ziifra-accent-deep hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $runs->links() }}
    </div>
@endif
@endsection
