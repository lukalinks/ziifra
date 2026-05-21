@if ($myLeaveBalance)
    <section class="ziifra-dashboard-panel h-full">
        <div class="ziifra-dashboard-panel-head">
            <div>
                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.my_leave') }}</h3>
                <p class="text-xs text-ziifra-muted">
                    {{ __('dashboard.my_leave_subtitle', ['type' => $myLeaveBalance['type'], 'year' => $currentYear]) }}
                </p>
            </div>
        </div>
        <div class="flex flex-col items-center px-5 py-6">
            @php
                $usedPercent = $myLeaveBalance['entitled'] > 0
                    ? min(100, (int) round(($myLeaveBalance['used'] / $myLeaveBalance['entitled']) * 100))
                    : 0;
            @endphp
            <div class="ziifra-dashboard-balance-ring" style="background: conic-gradient(var(--color-ziifra-accent) {{ $usedPercent }}%, rgb(226 221 210 / 0.5) 0)">
                <div class="flex h-[5.5rem] w-[5.5rem] flex-col items-center justify-center rounded-full bg-ziifra-paper">
                    <div class="ziifra-dashboard-balance-value">
                        <span>{{ number_format($myLeaveBalance['remaining'], 0) }}</span>
                        <span class="mt-0.5 block text-[0.65rem] font-normal text-ziifra-muted">{{ __('dashboard.days_remaining_label') }}</span>
                    </div>
                </div>
            </div>
            <p class="mt-4 text-center text-sm text-ziifra-muted">
                {{ __('dashboard.days_used', ['used' => number_format($myLeaveBalance['used'], 1), 'total' => number_format($myLeaveBalance['entitled'], 0)]) }}
            </p>
            <a href="{{ route('leave.create') }}" data-page-nav class="ziifra-btn-app mt-4 w-full justify-center">
                {{ __('dashboard.request_leave') }}
            </a>
        </div>
    </section>
@endif