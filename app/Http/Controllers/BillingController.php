<?php



namespace App\Http\Controllers;



use App\Enums\PaymentProvider;

use App\Enums\SubscriptionPlan;

use App\Services\BillingConfigurationService;

use App\Services\OrganizationBillingService;

use App\Services\PayPalBillingService;

use App\Services\StripeBillingService;

use App\Support\CurrentOrganization;

use App\Support\PayPalConfig;

use App\Support\StripeConfig;

use App\Support\Workspace;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use Illuminate\View\View;



class BillingController extends Controller

{

    public function show(

        OrganizationBillingService $billing,

        StripeBillingService $stripe,

        PayPalBillingService $paypal,

        BillingConfigurationService $billingConfig,

    ): View {

        $organization = CurrentOrganization::check();

        $role = auth()->user()->roleIn($organization);



        if (! ($role?->canManageBilling() ?? false)) {

            abort(403);

        }



        $selectablePlans = array_filter(

            SubscriptionPlan::selectable(),

            fn (SubscriptionPlan $plan) => $plan !== SubscriptionPlan::Enterprise

                || StripeConfig::priceIdFor($plan) !== null,

        );



        $stripeDiagnostics = StripeConfig::checkoutDiagnostics();

        $stripeFullDiagnostics = StripeConfig::fullDiagnostics();

        $paypalDiagnostics = PayPalConfig::checkoutDiagnostics();

        $paypalFullDiagnostics = PayPalConfig::fullDiagnostics();



        return view('app.settings.billing', [

            'organization' => $organization,

            'billing' => $billing,

            'currentPlan' => $billing->plan($organization),

            'plans' => $selectablePlans,

            'employeeCount' => $billing->activeEmployeeCount($organization),

            'employeeLimit' => $billing->employeeLimit($organization),

            'trialDaysRemaining' => $billing->trialDaysRemaining($organization),

            'hasActiveAccess' => $billing->hasActiveAccess($organization),

            'stripeEnabled' => $stripe->isConfigured(),

            'stripeCheckoutReady' => $stripeDiagnostics['ready'],
            'stripeMissingConfig' => $stripeDiagnostics['missing'],
            'stripeMissingEnvConfig' => $stripeDiagnostics['missing_env'],
            'stripeMissingAdminConfig' => $stripeDiagnostics['missing_admin'],
            'stripeWebhookReady' => $stripeFullDiagnostics['webhook_ready'],
            'stripeMissingWebhookConfig' => $stripeFullDiagnostics['missing_webhook'],
            'paypalEnabled' => $paypal->isConfigured(),
            'paypalCheckoutReady' => $paypalDiagnostics['ready'],
            'paypalMissingConfig' => $paypalDiagnostics['missing'],
            'paypalMissingEnvConfig' => $paypalDiagnostics['missing_env'],
            'paypalMissingAdminConfig' => $paypalDiagnostics['missing_admin'],
            'paypalWebhookReady' => $paypalFullDiagnostics['webhook_ready'],
            'paypalMissingWebhookConfig' => $paypalFullDiagnostics['missing_webhook'],
            'paypalMode' => $paypalFullDiagnostics['mode'],
            'allowsManualUpgrade' => $billing->allowsManualPlanSelection(),
            'appUrl' => config('app.url'),
            'isPlatformAdmin' => auth()->user()->isSuperAdmin(),
            'adminBillingUrl' => route('admin.billing.edit'),

            'hasPaidSubscription' => $billing->hasActivePaidSubscription($organization),

            'hasStripeSubscription' => $billing->hasActiveStripeSubscription($organization),

            'hasPayPalSubscription' => $billing->hasActivePayPalSubscription($organization),

            'canManagePortal' => $stripe->isConfigured() && $organization->stripe_id !== null,

            'planCatalog' => $billingConfig->plans(),

        ]);

    }



    public function checkout(

        Request $request,

        OrganizationBillingService $billing,

        StripeBillingService $stripe,

        PayPalBillingService $paypal,

    ): RedirectResponse {

        $organization = CurrentOrganization::check();

        $this->authorizeBilling($organization);



        $validated = $request->validate([

            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],

            'provider' => ['nullable', Rule::enum(PaymentProvider::class)],

        ]);



        $plan = SubscriptionPlan::from($validated['plan']);



        if ($plan === SubscriptionPlan::Enterprise) {

            return back()->with('error', __('billing.enterprise_contact'));

        }



        $provider = isset($validated['provider'])

            ? PaymentProvider::from($validated['provider'])

            : $this->defaultProvider($stripe, $paypal);



        if ($provider === PaymentProvider::PayPal && $paypal->isConfigured()) {

            if (! PayPalConfig::isCheckoutReadyFor($plan)) {
                return back()->with('error', __('billing.paypal_plan_missing', [
                    'plan' => $plan->label(),
                ]));
            }



            try {

                $url = $paypal->subscriptionApproveUrl($organization, $plan, $request->user());



                return redirect()->away($url);

            } catch (\Throwable $exception) {

                report($exception);



                return back()->with('error', __('billing.paypal_checkout_error', [

                    'message' => $exception->getMessage(),

                ]));

            }

        }



        if ($provider === PaymentProvider::Stripe && $stripe->isConfigured()) {

            if (! StripeConfig::isCheckoutReadyFor($plan)) {
                return back()->with('error', __('billing.stripe_price_missing', [
                    'plan' => $plan->label(),
                ]));
            }



            try {

                $url = $stripe->checkoutUrl($organization, $plan, $request->user());



                if ($url === null || $url === '') {

                    return back()->with('error', __('billing.stripe_session_failed'));

                }



                return redirect()->away($url);

            } catch (\Throwable $exception) {

                report($exception);



                return back()->with('error', __('billing.stripe_checkout_error', [

                    'message' => $exception->getMessage(),

                ]));

            }

        }



        if ($billing->allowsManualPlanSelection() && $billing->canSelectPlan($plan)) {

            $billing->activatePlan($organization, $plan);



            return redirect()

                ->to(Workspace::route('settings.billing', $organization).'#plans')

                ->with('status', __('billing.plan_activated', ['plan' => $plan->label()]));

        }



        return back()->with('error', __('billing.checkout_unavailable'));

    }



    public function checkoutSuccess(Request $request, StripeBillingService $stripe): RedirectResponse

    {

        $organization = CurrentOrganization::check();

        $this->authorizeBilling($organization);



        $sessionId = $request->string('session_id')->toString();



        if ($sessionId !== '' && $stripe->isConfigured()) {

            try {

                $stripe->syncCheckoutSession($sessionId, $organization);

            } catch (\Throwable) {

                // Webhook will sync if success page races.

            }

        }



        return redirect()

            ->to(Workspace::route('settings.billing', $organization))

            ->with('status', __('billing.checkout_success'));

    }



    public function paypalSuccess(Request $request, PayPalBillingService $paypal): RedirectResponse

    {

        $organization = CurrentOrganization::check();

        $this->authorizeBilling($organization);



        $subscriptionId = $request->string('subscription_id')->toString();



        if ($subscriptionId !== '' && $paypal->isConfigured()) {

            try {

                $paypal->syncSubscription($subscriptionId, $organization);

            } catch (\Throwable) {

                // Webhook will sync if success page races.

            }

        }



        return redirect()

            ->to(Workspace::route('settings.billing', $organization))

            ->with('status', __('billing.checkout_success'));

    }



    public function portal(StripeBillingService $stripe): RedirectResponse

    {

        $organization = CurrentOrganization::check();

        $this->authorizeBilling($organization);



        try {

            return redirect()->away($stripe->portalUrl($organization));

        } catch (\Throwable $exception) {

            return back()->with('error', $exception->getMessage());

        }

    }



    protected function authorizeBilling(\App\Models\Organization $organization): void

    {

        $role = auth()->user()->roleIn($organization);



        if (! ($role?->canManageBilling() ?? false)) {

            abort(403);

        }

    }



    protected function defaultProvider(StripeBillingService $stripe, PayPalBillingService $paypal): PaymentProvider

    {

        if ($stripe->isConfigured()) {

            return PaymentProvider::Stripe;

        }



        if ($paypal->isConfigured()) {

            return PaymentProvider::PayPal;

        }



        return PaymentProvider::Stripe;

    }

}

