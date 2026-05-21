@props([
    'labels' => [],
    'approved' => [],
    'pending' => [],
])

<section {{ $attributes->class(['ziifra-dashboard-panel']) }}>
    <div class="ziifra-dashboard-panel-head">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('admin_dashboard.leave_trend_title') }}</h3>
            <p class="text-xs text-ziifra-muted">{{ __('admin_dashboard.leave_trend_hint') }}</p>
        </div>
    </div>
    <div class="p-4">
        <div
            class="relative h-56 w-full"
            data-leave-trend-chart
            data-labels='@json($labels)'
            data-approved='@json($approved)'
            data-pending='@json($pending)'
            data-label-approved="{{ __('admin_dashboard.chart_approved_days') }}"
            data-label-pending="{{ __('admin_dashboard.chart_pending_count') }}"
        >
            <canvas aria-label="{{ __('admin_dashboard.leave_trend_title') }}"></canvas>
        </div>
    </div>
</section>
