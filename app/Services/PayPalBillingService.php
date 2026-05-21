<?php

namespace App\Services;

use App\Enums\PaymentProvider;
use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Models\User;
use App\Support\PayPalConfig;
use App\Support\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalBillingService
{
    public function __construct(
        protected BillingNotificationService $billingNotifications,
    ) {}

    public function isConfigured(): bool
    {
        return PayPalConfig::isConfigured();
    }

    public function subscriptionApproveUrl(Organization $organization, SubscriptionPlan $plan, User $user): string
    {
        $this->ensureConfigured();

        $paypalPlanId = PayPalConfig::planIdFor($plan);

        if ($paypalPlanId === null) {
            throw new \InvalidArgumentException('This plan is not available for PayPal checkout.');
        }

        $response = $this->request('POST', '/v1/billing/subscriptions', [
            'plan_id' => $paypalPlanId,
            'custom_id' => $this->customId($organization, $plan),
            'subscriber' => [
                'email_address' => $user->email,
                'name' => [
                    'given_name' => Str::before($user->name, ' ') ?: $user->name,
                    'surname' => Str::after($user->name, ' ') ?: $user->name,
                ],
            ],
            'application_context' => [
                'brand_name' => config('app.name', 'ZIIFRA'),
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => Workspace::route('settings.billing.paypal.success', $organization, [], true),
                'cancel_url' => Workspace::route('settings.billing', $organization, [], true).'#plans',
            ],
        ]);

        $approveUrl = collect($response['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! is_string($approveUrl) || $approveUrl === '') {
            throw new \RuntimeException('PayPal did not return an approval URL.');
        }

        return $approveUrl;
    }

    public function syncSubscription(string $subscriptionId, Organization $organization): void
    {
        $this->ensureConfigured();

        $subscription = $this->request('GET', '/v1/billing/subscriptions/'.$subscriptionId);

        $this->assertSubscriptionBelongsToOrganization($subscription, $organization);
        $this->applySubscription($organization, $subscription);
    }

    /**
     * @param  array<string, string|null>  $headers
     */
    public function handleWebhook(array $headers, string $payload): void
    {
        $this->ensureConfigured();

        $event = filled(config('paypal.webhook_id'))
            ? $this->verifyWebhook($headers, $payload)
            : json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($event)) {
            return;
        }

        $eventType = (string) ($event['event_type'] ?? '');
        $resource = $event['resource'] ?? null;

        if (! is_array($resource)) {
            return;
        }

        match ($eventType) {
            'BILLING.SUBSCRIPTION.ACTIVATED',
            'BILLING.SUBSCRIPTION.RE-ACTIVATED',
            'BILLING.SUBSCRIPTION.UPDATED' => $this->handleSubscriptionActivated($resource),
            'BILLING.SUBSCRIPTION.SUSPENDED',
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => $this->handleSubscriptionSuspended($resource),
            'BILLING.SUBSCRIPTION.CANCELLED',
            'BILLING.SUBSCRIPTION.EXPIRED' => $this->handleSubscriptionCancelled($resource),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function handleSubscriptionActivated(array $subscription): void
    {
        $organization = $this->organizationFromSubscription($subscription);

        if ($organization === null) {
            return;
        }

        $previousStatus = $organization->paypal_subscription_status;

        $this->applySubscription($organization, $subscription);

        $organization->refresh();

        $status = strtoupper((string) ($subscription['status'] ?? $organization->paypal_subscription_status));

        if (in_array($status, ['SUSPENDED', 'CANCELLED'], true)) {
            $this->billingNotifications->notifyPaymentFailed($organization);
        } elseif (
            in_array(strtoupper((string) $previousStatus), ['SUSPENDED', 'CANCELLED'], true)
            && $status === 'ACTIVE'
        ) {
            $this->billingNotifications->clearPaymentFailedReminder($organization);
        }
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function handleSubscriptionSuspended(array $subscription): void
    {
        $organization = $this->organizationFromSubscription($subscription);

        if ($organization === null) {
            return;
        }

        $this->applySubscription($organization, $subscription);
        $this->billingNotifications->notifyPaymentFailed($organization);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function handleSubscriptionCancelled(array $subscription): void
    {
        $organization = $this->organizationFromSubscription($subscription);

        if ($organization === null) {
            return;
        }

        $organization->update([
            'paypal_subscription_id' => null,
            'paypal_subscription_status' => 'CANCELLED',
            'paypal_subscription_ends_at' => now(),
            'billing_payment_provider' => null,
            'plan' => SubscriptionPlan::Trial->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function applySubscription(Organization $organization, array $subscription): void
    {
        $status = strtoupper((string) ($subscription['status'] ?? ''));
        $plan = $this->resolvePlan($subscription, $organization);
        $endsAt = $this->resolvePeriodEnd($subscription);

        $organization->update([
            'paypal_subscription_id' => (string) ($subscription['id'] ?? $organization->paypal_subscription_id),
            'paypal_subscription_status' => $status !== '' ? $status : $organization->paypal_subscription_status,
            'paypal_subscription_ends_at' => $endsAt,
            'billing_payment_provider' => PaymentProvider::PayPal->value,
            'plan' => $plan->value,
            'trial_ends_at' => in_array($status, ['ACTIVE', 'APPROVAL_PENDING'], true) ? null : $organization->trial_ends_at,
        ]);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function resolvePlan(array $subscription, ?Organization $organization = null): SubscriptionPlan
    {
        $planId = $subscription['plan_id'] ?? null;

        if (is_string($planId)) {
            $plan = PayPalConfig::planForPayPalPlanId($planId);

            if ($plan instanceof SubscriptionPlan) {
                return $plan;
            }
        }

        [, , $planValue] = $this->parseCustomId((string) ($subscription['custom_id'] ?? ''));

        if ($planValue !== null) {
            $plan = SubscriptionPlan::tryFrom($planValue);

            if ($plan instanceof SubscriptionPlan) {
                return $plan;
            }
        }

        if ($organization !== null) {
            return app(OrganizationBillingService::class)->plan($organization);
        }

        return SubscriptionPlan::Starter;
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function organizationFromSubscription(array $subscription): ?Organization
    {
        [$organizationId] = $this->parseCustomId((string) ($subscription['custom_id'] ?? ''));

        if ($organizationId !== null) {
            return Organization::query()->find($organizationId);
        }

        $subscriptionId = $subscription['id'] ?? null;

        if (is_string($subscriptionId) && $subscriptionId !== '') {
            return Organization::query()
                ->where('paypal_subscription_id', $subscriptionId)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function assertSubscriptionBelongsToOrganization(array $subscription, Organization $organization): void
    {
        [$organizationId] = $this->parseCustomId((string) ($subscription['custom_id'] ?? ''));

        if ($organizationId !== null && $organizationId !== $organization->id) {
            throw new \RuntimeException('PayPal subscription does not belong to this organization.');
        }

        if ($organizationId === null && filled($organization->paypal_subscription_id)) {
            $subscriptionId = (string) ($subscription['id'] ?? '');

            if ($subscriptionId !== '' && $subscriptionId !== $organization->paypal_subscription_id) {
                throw new \RuntimeException('PayPal subscription does not belong to this organization.');
            }
        }
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: string|null}
     */
    protected function parseCustomId(string $customId): array
    {
        if ($customId === '') {
            return [null, null, null];
        }

        if (preg_match('/^org:(\d+):plan:([a-z]+)$/', $customId, $matches) === 1) {
            return [(int) $matches[1], 'org', $matches[2]];
        }

        return [null, null, null];
    }

    protected function customId(Organization $organization, SubscriptionPlan $plan): string
    {
        return 'org:'.$organization->id.':plan:'.$plan->value;
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    protected function resolvePeriodEnd(array $subscription): ?\Carbon\Carbon
    {
        $billingInfo = $subscription['billing_info'] ?? null;

        if (! is_array($billingInfo)) {
            return null;
        }

        $nextBilling = $billingInfo['next_billing_time'] ?? null;

        if (is_string($nextBilling) && $nextBilling !== '') {
            return \Carbon\Carbon::parse($nextBilling);
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $headers
     * @return array<string, mixed>
     */
    protected function verifyWebhook(array $headers, string $payload): array
    {
        $verification = $this->request('POST', '/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'] ?? $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url' => $headers['paypal-cert-url'] ?? $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id' => $headers['paypal-transmission-id'] ?? $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'] ?? $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => config('paypal.webhook_id'),
            'webhook_event' => json_decode($payload, true, 512, JSON_THROW_ON_ERROR),
        ]);

        if (($verification['verification_status'] ?? '') !== 'SUCCESS') {
            throw new \UnexpectedValueException('Invalid PayPal webhook signature.');
        }

        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return is_array($event) ? $event : [];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path, array $body = []): array
    {
        $request = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->baseUrl(PayPalConfig::apiBaseUrl());

        $response = match (strtoupper($method)) {
            'GET' => $request->get($path),
            'POST' => $request->post($path, $body),
            'PATCH' => $request->patch($path, $body),
            default => throw new \InvalidArgumentException("Unsupported HTTP method [{$method}]."),
        };

        if (! $response->successful()) {
            throw new \RuntimeException('PayPal API error: '.$response->body());
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    protected function accessToken(): string
    {
        return Cache::remember('paypal.access_token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->withBasicAuth(
                    (string) config('paypal.client_id'),
                    (string) config('paypal.client_secret'),
                )
                ->acceptJson()
                ->post(PayPalConfig::apiBaseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Unable to authenticate with PayPal.');
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new \RuntimeException('PayPal access token missing from response.');
            }

            return $token;
        });
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PayPal is not configured.');
        }
    }
}
