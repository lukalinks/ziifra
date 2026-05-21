@props(['days'])

<section class="ziifra-dashboard-week" aria-label="{{ __('dashboard.week_ahead') }}">
    <div class="ziifra-dashboard-panel-head !rounded-t-2xl">
        <div>
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.week_ahead') }}</h3>
            <p class="text-xs text-ziifra-muted">{{ __('dashboard.week_ahead_subtitle') }}</p>
        </div>
        <a href="{{ route('leave.calendar') }}" class="text-xs font-medium text-ziifra-accent-deep hover:underline">
            {{ __('dashboard.view_calendar') }} →
        </a>
    </div>
    <div class="grid grid-cols-7 gap-2 p-4 sm:gap-3">
        @foreach ($days as $day)
            <a href="{{ route('leave.calendar', ['year' => $day['date']->year, 'month' => $day['date']->month]) }}"
                class="ziifra-dashboard-week-day group {{ $day['is_today'] ? 'ziifra-dashboard-week-day-today' : '' }}"
                title="{{ $day['date']->format('l, j M') }}">
                <span class="text-[0.65rem] font-medium uppercase tracking-wide text-ziifra-muted group-hover:text-ziifra-ink">
                    {{ $day['label'] }}
                </span>
                <span class="mt-1 text-lg font-semibold tabular-nums text-ziifra-ink">{{ $day['short_label'] }}</span>
                <span class="ziifra-dashboard-week-count {{ $day['count'] > 0 ? 'ziifra-dashboard-week-count-active' : '' }}">
                    {{ $day['count'] }}
                </span>
                <span class="sr-only">{{ trans_choice('dashboard.people_count', $day['count'], ['count' => $day['count']]) }}</span>
            </a>
        @endforeach
    </div>
</section>
