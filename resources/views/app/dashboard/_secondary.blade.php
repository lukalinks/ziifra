<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    @if ($myLeaveBalance)
        <section class="ziifra-dashboard-panel">
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
                            <span class="mt-0.5 block text-[0.65rem] font-normal text-ziifra-muted">days left</span>
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-center text-sm text-ziifra-muted">
                    {{ __('dashboard.days_used', ['used' => number_format($myLeaveBalance['used'], 1), 'total' => number_format($myLeaveBalance['entitled'], 0)]) }}
                </p>
                <a href="{{ route('leave.create') }}" class="ziifra-btn-app mt-4 w-full justify-center sm:w-auto">
                    {{ __('dashboard.request_leave') }}
                </a>
            </div>
        </section>
    @endif

    @if ($canManageEmployees && $expiringDocuments->isNotEmpty())
        <section class="ziifra-dashboard-panel">
            <div class="ziifra-dashboard-panel-head">
                <div>
                    <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.expiring_documents_list') }}</h3>
                    <p class="text-xs text-ziifra-muted">{{ __('dashboard.expiring_documents_hint') }}</p>
                </div>
                <span class="ziifra-dashboard-badge">{{ $expiringDocumentCount }}</span>
            </div>
            <ul class="space-y-0.5 p-3">
                @foreach ($expiringDocuments as $document)
                    @php
                        $daysLeft = (int) now()->startOfDay()->diffInDays($document->expires_at->copy()->startOfDay(), false);
                    @endphp
                    <li class="ziifra-dashboard-list-row">
                        <div class="min-w-0">
                            <a href="{{ route('employees.show', $document->employee) }}" class="block truncate">
                                {{ $document->title }}
                            </a>
                            <p class="truncate text-xs text-ziifra-muted">{{ $document->employee?->fullName() }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-medium {{ $daysLeft < 0 ? 'text-red-700' : ($daysLeft <= 7 ? 'text-amber-800' : 'text-ziifra-muted') }}">
                            @if ($daysLeft < 0)
                                {{ __('dashboard.expired') }}
                            @else
                                {{ __('dashboard.days_left', ['count' => max(0, $daysLeft)]) }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="ziifra-dashboard-panel-foot">
                <a href="{{ route('employees.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                    {{ __('dashboard.view_directory') }} →
                </a>
            </div>
        </section>
    @endif

    @if ($canViewEmployees && $recentHires->isNotEmpty())
        <section class="ziifra-dashboard-panel">
            <div class="ziifra-dashboard-panel-head">
                <div>
                    <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.recent_hires') }}</h3>
                    <p class="text-xs text-ziifra-muted">{{ __('dashboard.recent_hires_subtitle') }}</p>
                </div>
            </div>
            <ul class="space-y-0.5 p-3">
                @foreach ($recentHires as $hire)
                    <li class="ziifra-dashboard-list-row">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="ziifra-dashboard-avatar">{{ $hire->initials() }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('employees.show', $hire) }}" class="block truncate">{{ $hire->fullName() }}</a>
                                @if ($hire->department)
                                    <p class="truncate text-xs text-ziifra-muted">{{ $hire->department->name }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 text-xs text-ziifra-muted">
                            {{ __('dashboard.started_on', ['date' => $hire->start_date->format('M j')]) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($draftPayrollRun)
        <section class="ziifra-dashboard-panel border-ziifra-accent/20 bg-gradient-to-br from-ziifra-accent/[0.04] to-ziifra-paper">
            <div class="ziifra-dashboard-panel-head">
                <div>
                    <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('dashboard.payroll_draft') }}</h3>
                    <p class="text-xs text-ziifra-muted">
                        {{ __('dashboard.payroll_draft_hint', ['period' => $draftPayrollRun->periodLabel()]) }}
                    </p>
                </div>
            </div>
            <div class="p-5 pt-2">
                <a href="{{ $draftPayrollRun->showUrl() }}" class="ziifra-btn-app w-full justify-center">
                    {{ __('dashboard.open_payroll') }}
                </a>
            </div>
        </section>
    @endif
</div>
