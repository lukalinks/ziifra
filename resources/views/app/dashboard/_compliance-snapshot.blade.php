<section class="ziifra-dashboard-panel ziifra-admin-dashboard-compliance">
    <div class="ziifra-dashboard-panel-head">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('admin_dashboard.compliance_snapshot') }}</h3>
            <p class="text-xs text-ziifra-muted">{{ __('admin_dashboard.compliance_hint') }}</p>
        </div>
    </div>
    <ul class="divide-y divide-ziifra-line/60 p-2">
        @if ($expiringDocumentCount > 0)
            <li>
                <a href="{{ route('employees.index') }}" class="ziifra-admin-dashboard-compliance-row group">
                    <span class="ziifra-admin-dashboard-compliance-icon ziifra-admin-dashboard-compliance-icon-warn" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-ziifra-ink">{{ __('dashboard.expiring_documents') }}</span>
                        <span class="mt-0.5 block text-xs text-ziifra-muted">{{ __('admin_dashboard.compliance_expiring_hint') }}</span>
                    </span>
                    <span class="ziifra-admin-dashboard-compliance-count ziifra-admin-dashboard-compliance-count-warn">{{ $expiringDocumentCount }}</span>
                    <svg class="ziifra-admin-dashboard-compliance-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </li>
        @endif
        @if ($employeesMissingLogin > 0)
            <li>
                <a href="{{ route('employees.index', ['missing_login' => 1]) }}" class="ziifra-admin-dashboard-compliance-row group">
                    <span class="ziifra-admin-dashboard-compliance-icon ziifra-admin-dashboard-compliance-icon-accent" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-ziifra-ink">{{ __('admin_dashboard.stat_missing_login') }}</span>
                        <span class="mt-0.5 block text-xs text-ziifra-muted">{{ __('admin_dashboard.compliance_missing_login_hint') }}</span>
                    </span>
                    <span class="ziifra-admin-dashboard-compliance-count">{{ $employeesMissingLogin }}</span>
                    <svg class="ziifra-admin-dashboard-compliance-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </li>
        @endif
    </ul>
</section>
