@if ($canManageOrganization && ! $organization->isProfileComplete())
    <div class="ziifra-profile-nudge" role="status">
        <span class="ziifra-profile-nudge-icon" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
            </svg>
        </span>
        <p class="ziifra-profile-nudge-text min-w-0 flex-1 font-medium text-ziifra-ink">
            {{ __('dashboard.complete_profile_short') }}
        </p>
        <a href="{{ $settingsUrl ?? route('settings.company.edit') }}" class="ziifra-profile-nudge-btn" data-page-nav>
            {{ __('dashboard.setup_company') }}
        </a>
    </div>
@endif
