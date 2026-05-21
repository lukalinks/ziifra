<?php



namespace App\Services;



use App\Enums\EmploymentStatus;

use App\Enums\OrganizationRole;

use App\Enums\SubscriptionPlan;

use App\Models\Organization;

use App\Support\PayPalConfig;

use App\Support\StripeConfig;

use Carbon\Carbon;

use Illuminate\Support\Collection;



class OrganizationBillingService

{

    public function __construct(

        protected BillingConfigurationService $billingConfig,

    ) {}



    public function plan(Organization $organization): SubscriptionPlan

    {

        $plan = $organization->plan;



        if ($plan instanceof SubscriptionPlan) {

            return $plan;

        }



        return SubscriptionPlan::tryFrom((string) ($plan ?? '')) ?? SubscriptionPlan::Trial;

    }



    public function employeeLimit(Organization $organization): ?int

    {

        $limit = $this->billingConfig->plan($this->plan($organization)->value)['employee_limit'] ?? null;



        return is_int($limit) ? $limit : null;

    }



    public function activeEmployeeCount(Organization $organization): int

    {

        return $organization->employees()

            ->where('employment_status', '!=', EmploymentStatus::Terminated->value)

            ->count();

    }



    public function canAddEmployee(Organization $organization): bool

    {

        if (! $this->hasActiveAccess($organization)) {

            return false;

        }



        $limit = $this->employeeLimit($organization);



        if ($limit === null) {

            return true;

        }



        return $this->activeEmployeeCount($organization) < $limit;

    }



    public function isSuspended(Organization $organization): bool

    {

        return $organization->suspended_at !== null;

    }



    public function isOnTrial(Organization $organization): bool

    {

        return $this->plan($organization) === SubscriptionPlan::Trial;

    }



    public function trialEndsAt(Organization $organization): ?Carbon

    {

        if (! $this->isOnTrial($organization)) {

            return null;

        }



        if ($organization->trial_ends_at !== null) {

            return $organization->trial_ends_at;

        }



        if ($organization->created_at === null) {

            return null;

        }



        return $organization->created_at->copy()->addDays($this->billingConfig->trialDays());

    }



    public function trialExpired(Organization $organization): bool

    {

        if (! $this->isOnTrial($organization)) {

            return false;

        }



        $endsAt = $this->trialEndsAt($organization);



        return $endsAt !== null && $endsAt->isPast();

    }



    public function hasActiveStripeSubscription(Organization $organization): bool

    {

        return in_array($organization->stripe_subscription_status, ['active', 'trialing'], true);

    }



    public function hasActivePayPalSubscription(Organization $organization): bool

    {

        return in_array(strtoupper((string) $organization->paypal_subscription_status), ['ACTIVE', 'APPROVAL_PENDING'], true);

    }



    public function hasActivePaidSubscription(Organization $organization): bool

    {

        return $this->hasActiveStripeSubscription($organization)

            || $this->hasActivePayPalSubscription($organization);

    }



    public function hasActiveAccess(Organization $organization): bool

    {

        if ($this->isSuspended($organization)) {

            return false;

        }



        if ($this->hasActivePaidSubscription($organization)) {

            return true;

        }



        if (! $this->isOnTrial($organization)) {

            return true;

        }



        return ! $this->trialExpired($organization);

    }



    public function trialDaysRemaining(Organization $organization): ?int

    {

        if (! $this->isOnTrial($organization)) {

            return null;

        }



        $endsAt = $this->trialEndsAt($organization);



        if ($endsAt === null) {

            return $this->billingConfig->trialDays();

        }



        if ($endsAt->isPast()) {

            return 0;

        }



        return max(0, (int) now()->startOfDay()->diffInDays(

            $endsAt->copy()->startOfDay(),

        ));

    }



    public function hasFeature(Organization $organization, \App\Enums\PlanFeature|string $feature): bool
    {
        $featureKey = $feature instanceof \App\Enums\PlanFeature ? $feature->value : $feature;
        $enabled = $this->billingConfig->enabledFeatures($this->plan($organization)->value);

        return in_array($featureKey, $enabled, true);
    }

    public function hasPayroll(Organization $organization): bool
    {
        return $this->hasFeature($organization, \App\Enums\PlanFeature::Payroll);
    }



    public function allowsManualPlanSelection(): bool

    {

        if (StripeConfig::isConfigured() || PayPalConfig::isConfigured()) {

            return false;

        }



        if (config('billing.allow_manual_upgrade')) {

            return true;

        }



        return app()->environment('local');

    }



    public function canPurchasePlanOnline(SubscriptionPlan $plan): bool

    {

        return StripeConfig::isCheckoutReadyFor($plan) || PayPalConfig::isCheckoutReadyFor($plan);

    }



    public function canPurchasePlanViaStripe(SubscriptionPlan $plan): bool

    {

        return StripeConfig::isCheckoutReadyFor($plan);

    }



    public function canPurchasePlanViaPayPal(SubscriptionPlan $plan): bool

    {

        return PayPalConfig::isCheckoutReadyFor($plan);

    }



    public function canSelectPlan(SubscriptionPlan $plan): bool

    {

        if ($plan === SubscriptionPlan::Enterprise) {

            return false;

        }



        if ($plan === SubscriptionPlan::Trial) {

            return false;

        }



        return $this->canPurchasePlanOnline($plan) || $this->allowsManualPlanSelection();

    }



    public function activatePlan(Organization $organization, SubscriptionPlan $plan): void

    {

        if (in_array($plan, [SubscriptionPlan::Trial, SubscriptionPlan::Enterprise], true)) {

            throw new \InvalidArgumentException('This plan cannot be activated here.');

        }



        $organization->update([

            'plan' => $plan->value,

            'trial_ends_at' => null,

        ]);

    }



    /**

     * @return array{name: string, employee_limit: int|null, payroll: bool, price_label: string}

     */

    public function planDetails(Organization $organization): array

    {

        return $this->billingConfig->plan($this->plan($organization)->value);

    }



    /**

     * @return Collection<int, string>

     */

    public function billingManagerEmails(Organization $organization): Collection

    {

        $emails = $organization->users()

            ->wherePivotIn('role', [

                OrganizationRole::Owner->value,

                OrganizationRole::Admin->value,

            ])

            ->pluck('email')

            ->filter()

            ->unique()

            ->values();



        if ($emails->isEmpty() && $organization->owner?->email) {

            return collect([$organization->owner->email]);

        }



        return $emails;

    }

}

