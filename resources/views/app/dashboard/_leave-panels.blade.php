<div class="grid gap-6 md:grid-cols-2">
    <section class="ziifra-dashboard-panel">
        <div class="ziifra-dashboard-panel-head">
            <div>
                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.out_today') }}</h3>
                <p class="text-xs text-ziifra-muted">
                    {{ trans_choice('dashboard.people_count', $outToday->count(), ['count' => $outToday->count()]) }}
                </p>
            </div>
            @if ($outToday->isNotEmpty())
                <span class="ziifra-dashboard-badge">{{ $outToday->count() }}</span>
            @endif
        </div>
        <div class="flex min-h-[12rem] flex-1 flex-col p-3">
            @if ($outToday->isEmpty())
                <div class="ziifra-dashboard-empty flex-1">
                    <span class="ziifra-dashboard-empty-icon text-ziifra-accent/70">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <p class="mt-3 text-sm text-ziifra-muted">{{ __('dashboard.out_today_empty') }}</p>
                </div>
            @else
                <ul class="space-y-0.5">
                    @foreach ($outToday as $request)
                        <li>
                            <x-dashboard.leave-row
                                :request="$request"
                                :badge="__('dashboard.until', ['date' => $request->end_date->format('M j')])"
                            />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="ziifra-dashboard-panel-foot">
            <a href="{{ route('leave.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                {{ __('dashboard.view_all_leave') }} →
            </a>
        </div>
    </section>

    <section class="ziifra-dashboard-panel">
        <div class="ziifra-dashboard-panel-head">
            <div>
                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.upcoming_leave') }}</h3>
                <p class="text-xs text-ziifra-muted">{{ __('dashboard.upcoming_leave_subtitle') }}</p>
            </div>
            @if ($upcomingLeave->isNotEmpty())
                <span class="ziifra-dashboard-badge">{{ $upcomingLeave->count() }}</span>
            @endif
        </div>
        <div class="flex min-h-[12rem] flex-1 flex-col p-3">
            @if ($upcomingLeave->isEmpty())
                <div class="ziifra-dashboard-empty flex-1">
                    <span class="ziifra-dashboard-empty-icon text-ziifra-muted/40">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <p class="mt-3 text-sm text-ziifra-muted">{{ __('dashboard.upcoming_leave_empty') }}</p>
                </div>
            @else
                <ul class="space-y-0.5">
                    @foreach ($upcomingLeave as $request)
                        <li>
                            <x-dashboard.leave-row :request="$request">
                                <span class="rounded-full bg-ziifra-accent/12 px-2 py-0.5 text-xs font-medium text-ziifra-accent-deep">
                                    {{ __('dashboard.starts', ['date' => $request->start_date->format('M j')]) }}
                                </span>
                            </x-dashboard.leave-row>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="ziifra-dashboard-panel-foot">
            <a href="{{ route('leave.calendar') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                {{ __('dashboard.view_calendar') }} →
            </a>
        </div>
    </section>
</div>
