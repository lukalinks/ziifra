@if (($showTrialUpgrade ?? false) || (($trialDaysRemaining ?? null) !== null && ($canManageBilling ?? false)))
    <div @class([
        'mb-4 flex flex-col gap-3 rounded-xl border px-4 py-4 sm:flex-row sm:items-center sm:justify-between',
        ($trialDaysRemaining ?? 0) <= 7 ? 'border-amber-300 bg-amber-50 text-amber-950' : 'border-ziifra-accent/30 bg-ziifra-accent/[0.08] text-ziifra-ink',
    ])>
        <p class="font-medium">
            @if (($trialDaysRemaining ?? 0) === 0)
                {{ __('billing.trial_ends_today') }}
            @else
                {{ __('billing.trial_banner', ['days' => $trialDaysRemaining]) }}
            @endif
        </p>
        <a href="{{ route('settings.billing') }}#plans"
            class="inline-flex shrink-0 items-center justify-center rounded-full bg-ziifra-accent px-5 py-2.5 text-sm font-semibold text-ziifra-on-accent shadow-sm transition hover:bg-ziifra-accent-glow focus:outline-none focus-visible:ring-2 focus-visible:ring-ziifra-accent/50">
            {{ __('billing.upgrade') }}
        </a>
    </div>
@endif
