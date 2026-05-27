@php
    $monthShort = \Carbon\Carbon::create($run->year, $run->month, 1)->format('M');
@endphp

<article class="ziifra-payroll-compact-card">
    <a href="{{ $run->showUrl() }}" class="ziifra-payroll-compact-card-main" data-page-nav>
        <span class="ziifra-payroll-compact-card-icon" aria-hidden="true">{{ $monthShort }}</span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $run->periodLabel() }}</span>
            <span class="mt-0.5 block text-xs text-ziifra-muted">{{ __('payroll.period') }}</span>
            <span class="mt-2 flex flex-wrap items-center gap-1.5">
                <span @class([
                    'ziifra-list-badge !text-[0.6rem]',
                    'ziifra-list-badge-success' => $run->isLocked(),
                    'ziifra-list-badge-warning' => ! $run->isLocked(),
                ])>{{ $run->status->label() }}</span>
            </span>
            <span class="mt-2 block text-[0.65rem] text-ziifra-muted">
                {{ $run->items_count }} {{ __('payroll.employees') }}
            </span>
        </span>
    </a>
    <div class="ziifra-payroll-compact-card-actions">
        <a href="{{ $run->showUrl() }}" class="ziifra-payroll-compact-card-link" data-page-nav>{{ __('payroll.view') }}</a>
    </div>
</article>
