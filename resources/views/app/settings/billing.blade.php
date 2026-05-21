@extends('layouts.app')

@section('title', 'Billing')
@section('header', 'Billing & plan')

@section('content')
<div class="max-w-3xl" id="plans">
    <p class="text-sm text-ziifra-muted">
        Manage your ZIIFRA subscription for <strong>{{ $organization->name }}</strong>.
    </p>

    @if ($isPlatformAdmin && ($stripeEnabled || $paypalEnabled))
        @if ($stripeEnabled)
            <div @class([
                'mt-4 rounded-lg border px-4 py-3 text-sm',
                $stripeCheckoutReady
                    ? 'border-green-200 bg-green-50 text-green-900'
                    : 'border-amber-200 bg-amber-50 text-amber-950',
            ])>
                <p class="font-medium">{{ __('billing.stripe_setup_title') }}</p>
                @if ($stripeCheckoutReady)
                    <p class="mt-1">{{ __('billing.stripe_setup_ready') }}</p>
                @else
                    <p class="mt-1">{{ __('billing.stripe_setup_incomplete') }}</p>
                    @if ($stripeMissingAdminConfig !== [])
                        <p class="mt-2 text-xs">{{ __('billing.configure_plans_in_admin') }}</p>
                        <ul class="mt-1 list-disc pl-5 text-xs">
                            @foreach ($stripeMissingAdminConfig as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-2">
                            <a href="{{ $adminBillingUrl }}" class="font-medium underline hover:no-underline">{{ __('billing.open_plans_billing') }}</a>
                        </p>
                    @endif
                    @if ($stripeMissingEnvConfig !== [])
                        <p class="mt-2 text-xs">{{ __('billing.configure_credentials_in_env') }}</p>
                        <ul class="mt-1 list-disc pl-5 font-mono text-xs">
                            @foreach ($stripeMissingEnvConfig as $key)
                                <li>{{ $key }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>

            @if (! $stripeWebhookReady)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    <p class="font-medium">{{ __('billing.stripe_webhook_setup_title') }}</p>
                    <p class="mt-1">{{ __('billing.stripe_webhook_setup_incomplete') }}</p>
                </div>
            @endif
        @endif

        @if ($paypalEnabled)
            <div @class([
                'mt-4 rounded-lg border px-4 py-3 text-sm',
                $paypalCheckoutReady
                    ? 'border-green-200 bg-green-50 text-green-900'
                    : 'border-amber-200 bg-amber-50 text-amber-950',
            ])>
                <p class="font-medium">{{ __('billing.paypal_setup_title') }}</p>
                @if ($paypalCheckoutReady)
                    <p class="mt-1">{{ __('billing.paypal_setup_ready', ['mode' => $paypalMode]) }}</p>
                @else
                    <p class="mt-1">{{ __('billing.paypal_setup_incomplete') }}</p>
                    @if ($paypalMissingAdminConfig !== [])
                        <p class="mt-2 text-xs">{{ __('billing.configure_plans_in_admin') }}</p>
                        <ul class="mt-1 list-disc pl-5 text-xs">
                            @foreach ($paypalMissingAdminConfig as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-2">
                            <a href="{{ $adminBillingUrl }}" class="font-medium underline hover:no-underline">{{ __('billing.open_plans_billing') }}</a>
                        </p>
                    @endif
                    @if ($paypalMissingEnvConfig !== [])
                        <p class="mt-2 text-xs">{{ __('billing.configure_credentials_in_env') }}</p>
                        <ul class="mt-1 list-disc pl-5 font-mono text-xs">
                            @foreach ($paypalMissingEnvConfig as $key)
                                <li>{{ $key }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>

            @if (! $paypalWebhookReady)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    <p class="font-medium">{{ __('billing.paypal_webhook_setup_title') }}</p>
                    <p class="mt-1">{{ __('billing.paypal_webhook_setup_incomplete') }}</p>
                </div>
            @endif
        @endif
    @elseif ($allowsManualUpgrade)
        <div class="mt-4 rounded-lg border border-ziifra-accent/25 bg-ziifra-accent/[0.06] px-4 py-3 text-sm text-ziifra-ink">
            {{ __('billing.manual_upgrade_hint') }}
        </div>
    @elseif (! $stripeEnabled && ! $paypalEnabled)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            {{ __('billing.checkout_not_configured') }}
            <a href="mailto:hello@ziifra.com" class="ml-1 font-medium underline hover:no-underline">{{ __('billing.upgrade_contact') }}</a>
        </div>
    @endif

    <div class="mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-ziifra-muted">{{ __('billing.current_plan') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ziifra-ink">{{ $currentPlan->label() }}</p>
                @if ($trialDaysRemaining !== null)
                    <p class="mt-2 text-sm text-amber-700">
                        @if ($trialDaysRemaining === 0)
                            {{ __('billing.trial_ends_today') }}
                        @else
                            {{ __('billing.trial_banner', ['days' => $trialDaysRemaining]) }}
                        @endif
                    </p>
                @endif
                @if ($hasStripeSubscription)
                    <p class="mt-2 text-sm text-green-700">{{ __('billing.subscription_active_stripe') }}</p>
                @elseif ($hasPayPalSubscription)
                    <p class="mt-2 text-sm text-green-700">{{ __('billing.subscription_active_paypal') }}</p>
                @endif
            </div>
            <div class="text-right text-sm text-ziifra-muted">
                @if ($employeeLimit !== null)
                    <p>{{ __('billing.employees_used', ['count' => $employeeCount, 'limit' => $employeeLimit]) }}</p>
                @else
                    <p>{{ __('billing.employees_used_unlimited', ['count' => $employeeCount]) }}</p>
                @endif
            </div>
        </div>

        @if (! $hasActiveAccess)
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ __('billing.trial_expired') }}
            </div>
        @endif

        @if ($canManagePortal)
            <form method="POST" action="{{ route('settings.billing.portal') }}" class="mt-6">
                @csrf
                <button type="submit" class="text-sm font-medium text-ziifra-accent-deep underline hover:no-underline">
                    {{ __('billing.manage_subscription') }}
                </button>
            </form>
        @endif
    </div>

    <h2 class="mt-10 text-lg font-semibold text-ziifra-ink">{{ __('billing.available_plans') }}</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        @foreach ($plans as $plan)
            @php
                $details = $planCatalog[$plan->value] ?? [];
                $canCheckoutStripe = $stripeEnabled && filled($details['stripe_price_id'] ?? null);
                $canCheckoutPayPal = $paypalEnabled && filled($details['paypal_plan_id'] ?? null);
                $canActivateManually = ($allowsManualUpgrade ?? false)
                    && ! $stripeEnabled
                    && ! $paypalEnabled
                    && $plan !== \App\Enums\SubscriptionPlan::Enterprise;
            @endphp
            <div @class([
                'rounded-xl border p-5 flex flex-col',
                $plan === $currentPlan ? 'border-ziifra-accent bg-ziifra-cream/40' : 'border-ziifra-line/80 bg-ziifra-paper',
            ])>
                <h3 class="font-semibold text-ziifra-ink">{{ $details['name'] ?? $plan->label() }}</h3>
                <p class="mt-1 text-sm text-ziifra-muted">{{ $details['price_label'] ?? '' }}</p>
                <ul class="mt-4 flex-1 space-y-1 text-sm text-ziifra-muted">
                    <li>
                        @if (($details['employee_limit'] ?? null) === null)
                            Unlimited employees
                        @else
                            Up to {{ $details['employee_limit'] }} employees
                        @endif
                    </li>
                    @foreach ($details['features'] ?? [] as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
                @if ($plan === $currentPlan)
                    <p class="mt-4 text-xs font-medium text-ziifra-accent-deep">{{ __('billing.current_plan_label') }}</p>
                @elseif ($canCheckoutStripe || $canCheckoutPayPal || $canActivateManually)
                    <div class="mt-4 space-y-2">
                        @if ($canCheckoutStripe)
                            <form method="POST" action="{{ route('settings.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->value }}">
                                <input type="hidden" name="provider" value="stripe">
                                <button type="submit" class="w-full rounded-lg bg-ziifra-accent px-4 py-2.5 text-sm font-semibold text-ziifra-on-accent hover:bg-ziifra-accent-glow">
                                    {{ __('billing.stripe_pay_with') }}
                                </button>
                            </form>
                        @endif
                        @if ($canCheckoutPayPal)
                            <form method="POST" action="{{ route('settings.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->value }}">
                                <input type="hidden" name="provider" value="paypal">
                                <button type="submit" class="w-full rounded-lg border border-[#0070ba] bg-[#0070ba] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#005ea6]">
                                    {{ __('billing.paypal_pay_with') }}
                                </button>
                            </form>
                        @endif
                        @if ($canActivateManually)
                            <form method="POST" action="{{ route('settings.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->value }}">
                                <button type="submit" class="w-full rounded-lg bg-ziifra-accent px-4 py-2.5 text-sm font-semibold text-ziifra-on-accent hover:bg-ziifra-accent-glow">
                                    {{ __('billing.choose_plan', ['plan' => $details['name'] ?? $plan->label()]) }}
                                </button>
                            </form>
                        @endif
                    </div>
                @elseif ($plan === \App\Enums\SubscriptionPlan::Enterprise)
                    <p class="mt-4 text-xs text-ziifra-muted">{{ __('billing.enterprise_contact') }}</p>
                @else
                    <p class="mt-4 text-xs text-ziifra-muted">{{ __('billing.checkout_unavailable') }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-8">
        <a href="{{ route('settings.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">← Back to settings</a>
    </p>
</div>
@endsection
