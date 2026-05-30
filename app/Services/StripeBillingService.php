<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Models\User;
use App\Support\StripeConfig;
use App\Support\Workspace;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeBillingService
{
    public function __construct(
        protected BillingNotificationService $billingNotifications,
    ) {
        if (StripeConfig::isConfigured()) {
            Stripe::setApiKey(config('stripe.secret'));
        }
    }

    public function isConfigured(): bool
    {
        return StripeConfig::isConfigured();
    }

    public function checkoutUrl(Organization $organization, SubscriptionPlan $plan, User $user): string
    {
        $this->ensureConfigured();

        app(StripePriceSyncService::class)->ensurePriceForPlan($plan);

        $priceId = StripeConfig::priceIdFor($plan);

        if ($priceId === null) {
            throw new \InvalidArgumentException('This plan is not available for online checkout.');
        }

        $customerId = $this->ensureCustomer($organization, $user);

        $successUrl = Workspace::route('settings.billing.checkout.success', $organization, [], true)
            .'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = Workspace::route('settings.billing', $organization, [], true).'#plans';

        $session = CheckoutSession::create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'customer_update' => ['address' => 'auto'],
            'payment_method_types' => config('stripe.payment_method_types', ['card']),
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $organization->id,
            'metadata' => [
                'organization_id' => (string) $organization->id,
                'plan' => $plan->value,
            ],
            'subscription_data' => [
                'metadata' => [
                    'organization_id' => (string) $organization->id,
                    'plan' => $plan->value,
                ],
            ],
        ]);

        return $session->url;
    }

    public function portalUrl(Organization $organization): string
    {
        $this->ensureConfigured();

        if ($organization->stripe_id === null) {
            throw new \RuntimeException('No Stripe customer for this organization.');
        }

        $session = BillingPortalSession::create([
            'customer' => $organization->stripe_id,
            'return_url' => Workspace::route('settings.billing', $organization, [], true),
        ]);

        return $session->url;
    }

    public function syncCheckoutSession(string $sessionId, Organization $organization): void
    {
        $this->ensureConfigured();

        $session = CheckoutSession::retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        if ((string) $session->client_reference_id !== (string) $organization->id) {
            throw new \RuntimeException('Checkout session does not belong to this organization.');
        }

        if ($session->customer !== null) {
            $organization->update(['stripe_id' => $session->customer]);
        }

        if ($session->subscription !== null) {
            $this->applySubscription($organization, $session->subscription);
        }
    }

    /**
     * @param  array<string, mixed>|object  $payload
     */
    public function handleWebhookPayload(string $payload, ?string $signature): void
    {
        $this->ensureConfigured();

        $secret = config('stripe.webhook_secret');

        if ($secret) {
            $event = Webhook::constructEvent($payload, $signature ?? '', $secret);
        } else {
            $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        }

        $type = is_object($event) ? ($event->type ?? null) : null;
        $object = is_object($event) ? ($event->data->object ?? null) : null;

        if ($object === null) {
            return;
        }

        match ($type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionEvent($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            'invoice.payment_failed' => $this->handlePaymentFailed($object),
            default => null,
        };
    }

    protected function handleSubscriptionEvent(object $subscription): void
    {
        $organization = $this->organizationFromStripeObject($subscription);

        if ($organization === null) {
            return;
        }

        $previousStatus = $organization->stripe_subscription_status;

        $this->applySubscription($organization, $subscription);

        $organization->refresh();

        $status = $subscription->status ?? $organization->stripe_subscription_status;

        if (in_array($status, ['past_due', 'unpaid'], true)) {
            $this->billingNotifications->notifyPaymentFailed($organization);
        } elseif (
            in_array($previousStatus, ['past_due', 'unpaid'], true)
            && in_array($status, ['active', 'trialing'], true)
        ) {
            $this->billingNotifications->clearPaymentFailedReminder($organization);
        }
    }

    protected function handlePaymentFailed(object $invoice): void
    {
        $organization = $this->organizationFromStripeObject($invoice);

        if ($organization === null) {
            return;
        }

        $this->billingNotifications->notifyPaymentFailed($organization);
    }

    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $organization = $this->organizationFromStripeObject($subscription);

        if ($organization === null) {
            return;
        }

        $organization->update([
            'stripe_subscription_id' => null,
            'stripe_subscription_status' => 'canceled',
            'stripe_subscription_ends_at' => now(),
            'plan' => SubscriptionPlan::Trial->value,
        ]);
    }

    protected function applySubscription(Organization $organization, object $subscription): void
    {
        $priceId = $subscription->items->data[0]->price->id ?? null;
        $plan = is_string($priceId) ? StripeConfig::planForPriceId($priceId) : null;

        if ($plan === null) {
            $plan = SubscriptionPlan::tryFrom($subscription->metadata->plan ?? '');
        }

        if (! $plan instanceof SubscriptionPlan) {
            $existing = $organization->plan;
            $plan = $existing instanceof SubscriptionPlan
                ? $existing
                : (SubscriptionPlan::tryFrom((string) $existing) ?? SubscriptionPlan::Starter);
        }

        $organization->update([
            'stripe_id' => $subscription->customer ?? $organization->stripe_id,
            'stripe_subscription_id' => $subscription->id,
            'stripe_subscription_status' => $subscription->status,
            'stripe_subscription_ends_at' => isset($subscription->current_period_end)
                ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                : null,
            'plan' => $plan->value,
            'trial_ends_at' => null,
        ]);
    }

    protected function organizationFromStripeObject(object $stripeObject): ?Organization
    {
        $organizationId = $stripeObject->metadata->organization_id ?? null;

        if ($organizationId !== null) {
            return Organization::query()->find($organizationId);
        }

        $customerId = $stripeObject->customer ?? null;

        if ($customerId === null) {
            return null;
        }

        return Organization::query()->where('stripe_id', $customerId)->first();
    }

    protected function ensureCustomer(Organization $organization, User $user): string
    {
        if ($organization->stripe_id !== null) {
            return $organization->stripe_id;
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $organization->displayName(),
            'metadata' => [
                'organization_id' => (string) $organization->id,
            ],
        ]);

        $organization->update(['stripe_id' => $customer->id]);

        return $customer->id;
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured.');
        }
    }
}
