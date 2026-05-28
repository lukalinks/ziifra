@if (($showTrialUpgrade ?? false) || (($trialDaysRemaining ?? null) !== null && ($canManageBilling ?? false)))
    <div @class([
        'ziifra-trial-nudge',
        'ziifra-trial-nudge--urgent' => ($trialDaysRemaining ?? 0) <= 7,
    ]) role="status">
        <span class="ziifra-trial-nudge-icon" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <p class="ziifra-trial-nudge-text min-w-0 flex-1">
            @if (($trialDaysRemaining ?? 0) === 0)
                {{ __('billing.trial_ends_today') }}
            @else
                {{ __('billing.trial_banner', ['days' => $trialDaysRemaining]) }}
            @endif
        </p>
        <a href="{{ route('settings.billing') }}#plans" class="ziifra-trial-nudge-btn" data-page-nav>
            {{ __('billing.upgrade') }}
        </a>
    </div>
@endif
