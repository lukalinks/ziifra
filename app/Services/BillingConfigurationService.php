<?php

namespace App\Services;

use App\Enums\PlanFeature;
use App\Enums\SubscriptionPlan;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class BillingConfigurationService
{
    public const SETTINGS_KEY = 'billing';

    private const CACHE_KEY = 'platform.billing';

    public function trialDays(): int
    {
        return (int) ($this->settings()['trial_days'] ?? config('billing.trial_days', 14));
    }

    /**
     * @return list<array{key: string, label: string, required: bool}>
     */
    public function featureCatalog(): array
    {
        return array_map(
            fn (PlanFeature $feature) => [
                'key' => $feature->value,
                'label' => $feature->label(),
                'required' => $feature->isRequired(),
            ],
            PlanFeature::cases(),
        );
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     employee_limit: int|null,
     *     payroll: bool,
     *     price_label: string,
     *     monthly_price: int|null,
     *     stripe_price_id: string|null,
     *     paypal_plan_id: string|null,
     *     enabled_features: list<string>,
     *     features: list<string>
     * }>
     */
    public function plans(): array
    {
        $defaults = $this->defaultPlans();
        $overrides = $this->settings()['plans'] ?? [];
        $merged = [];

        foreach ($defaults as $key => $default) {
            $override = is_array($overrides[$key] ?? null) ? $overrides[$key] : [];
            $merged[$key] = $this->mergePlan($default, $override);
            $merged[$key]['features'] = $this->marketingFeatures($merged[$key]['enabled_features']);
        }

        return $merged;
    }

    /**
     * @return array{
     *     name: string,
     *     employee_limit: int|null,
     *     payroll: bool,
     *     price_label: string,
     *     monthly_price: int|null,
     *     stripe_price_id: string|null,
     *     paypal_plan_id: string|null,
     *     enabled_features: list<string>,
     *     features: list<string>
     * }
     */
    public function plan(string $key): array
    {
        return $this->plans()[$key] ?? [];
    }

    /**
     * @return list<string>
     */
    public function enabledFeatures(string $planKey): array
    {
        return $this->plan($planKey)['enabled_features'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function configurablePlanKeys(): array
    {
        return array_map(
            fn (SubscriptionPlan $plan) => $plan->value,
            SubscriptionPlan::cases(),
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $plans
     */
    public function update(int $trialDays, array $plans, ?User $admin = null): void
    {
        if ($trialDays < 1) {
            throw new InvalidArgumentException('Trial length must be at least one day.');
        }

        $normalized = [];

        foreach ($this->configurablePlanKeys() as $key) {
            if (! array_key_exists($key, $plans)) {
                throw new InvalidArgumentException("Missing plan configuration for {$key}.");
            }

            $normalized[$key] = $this->normalizePlanInput($key, $plans[$key]);
        }

        PlatformSetting::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => [
                'trial_days' => $trialDays,
                'plans' => $normalized,
                'updated_by' => $admin?->id,
                'updated_at' => now()->toIso8601String(),
            ]],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     employee_limit: int|null,
     *     payroll: bool,
     *     price_label: string,
     *     monthly_price: int|null,
     *     stripe_price_id: string|null,
     *     paypal_plan_id: string|null,
     *     enabled_features: list<string>
     * }
     */
    protected function normalizePlanInput(string $key, array $input): array
    {
        $plan = SubscriptionPlan::from($key);
        $default = $this->defaultPlans()[$key];

        $employeeLimit = $input['employee_limit'] ?? null;
        if ($employeeLimit === '' || $employeeLimit === null) {
            $employeeLimit = $plan === SubscriptionPlan::Enterprise ? null : (int) ($default['employee_limit'] ?? 0);
        } else {
            $employeeLimit = (int) $employeeLimit;
        }

        if ($plan === SubscriptionPlan::Enterprise) {
            $employeeLimit = null;
        }

        $monthlyPrice = $input['monthly_price'] ?? null;
        if ($monthlyPrice === '' || $monthlyPrice === null) {
            $monthlyPrice = $default['monthly_price'] ?? null;
        } else {
            $monthlyPrice = (int) $monthlyPrice;
        }

        if ($plan === SubscriptionPlan::Trial) {
            $monthlyPrice = null;
        }

        $stripePriceId = $input['stripe_price_id'] ?? null;
        $stripePriceId = is_string($stripePriceId) && trim($stripePriceId) !== '' ? trim($stripePriceId) : null;

        $paypalPlanId = $input['paypal_plan_id'] ?? null;
        $paypalPlanId = is_string($paypalPlanId) && trim($paypalPlanId) !== '' ? trim($paypalPlanId) : null;

        $enabledFeatures = $this->normalizeEnabledFeatures($plan, $input, $default);

        return [
            'name' => trim((string) ($input['name'] ?? $default['name'])),
            'employee_limit' => $employeeLimit,
            'payroll' => in_array(PlanFeature::Payroll->value, $enabledFeatures, true),
            'price_label' => trim((string) ($input['price_label'] ?? $default['price_label'])),
            'monthly_price' => $monthlyPrice,
            'stripe_price_id' => $stripePriceId,
            'paypal_plan_id' => $paypalPlanId,
            'enabled_features' => $enabledFeatures,
        ];
    }

    /**
     * @param  array<string, mixed>  $default
     * @return list<string>
     */
    protected function normalizeEnabledFeatures(SubscriptionPlan $plan, array $input, array $default): array
    {
        if ($plan === SubscriptionPlan::Enterprise) {
            return PlanFeature::values();
        }

        $submitted = $input['enabled_features'] ?? [];
        if (! is_array($submitted)) {
            $submitted = [];
        }

        $enabledFeatures = $this->filterValidFeatureKeys($submitted);

        if ($enabledFeatures === [] && isset($default['enabled_features'])) {
            $enabledFeatures = $this->filterValidFeatureKeys($default['enabled_features']);
        }

        if (! in_array(PlanFeature::Employees->value, $enabledFeatures, true)) {
            $enabledFeatures[] = PlanFeature::Employees->value;
        }

        return array_values(array_unique($enabledFeatures));
    }

    /**
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $override
     * @return array{
     *     name: string,
     *     employee_limit: int|null,
     *     payroll: bool,
     *     price_label: string,
     *     monthly_price: int|null,
     *     stripe_price_id: string|null,
     *     paypal_plan_id: string|null,
     *     enabled_features: list<string>
     * }
     */
    protected function mergePlan(array $default, array $override): array
    {
        $employeeLimit = array_key_exists('employee_limit', $override)
            ? ($override['employee_limit'] === null ? null : (int) $override['employee_limit'])
            : ($default['employee_limit'] ?? null);

        $monthlyPrice = array_key_exists('monthly_price', $override)
            ? ($override['monthly_price'] === null ? null : (int) $override['monthly_price'])
            : ($default['monthly_price'] ?? null);

        $stripePriceId = $override['stripe_price_id'] ?? $default['stripe_price_id'] ?? null;
        if (! is_string($stripePriceId) || $stripePriceId === '') {
            $stripePriceId = null;
        }

        $paypalPlanId = $override['paypal_plan_id'] ?? $default['paypal_plan_id'] ?? null;
        if (! is_string($paypalPlanId) || $paypalPlanId === '') {
            $paypalPlanId = null;
        }

        $enabledFeatures = $this->resolveEnabledFeatures($default, $override);

        return [
            'name' => (string) ($override['name'] ?? $default['name']),
            'employee_limit' => $employeeLimit,
            'payroll' => in_array(PlanFeature::Payroll->value, $enabledFeatures, true),
            'price_label' => (string) ($override['price_label'] ?? $default['price_label']),
            'monthly_price' => $monthlyPrice,
            'stripe_price_id' => $stripePriceId,
            'paypal_plan_id' => $paypalPlanId,
            'enabled_features' => $enabledFeatures,
        ];
    }

    /**
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $override
     * @return list<string>
     */
    protected function resolveEnabledFeatures(array $default, array $override): array
    {
        if (array_key_exists('enabled_features', $override) && is_array($override['enabled_features'])) {
            $enabledFeatures = $this->filterValidFeatureKeys($override['enabled_features']);
        } else {
            $enabledFeatures = $this->filterValidFeatureKeys($default['enabled_features'] ?? []);

            if (filter_var($override['payroll'] ?? $default['payroll'] ?? false, FILTER_VALIDATE_BOOL)
                && ! in_array(PlanFeature::Payroll->value, $enabledFeatures, true)) {
                $enabledFeatures[] = PlanFeature::Payroll->value;
            }
        }

        if (! in_array(PlanFeature::Employees->value, $enabledFeatures, true)) {
            $enabledFeatures[] = PlanFeature::Employees->value;
        }

        return array_values(array_unique($enabledFeatures));
    }

    /**
     * @param  list<string>  $enabledFeatures
     * @return list<string>
     */
    protected function marketingFeatures(array $enabledFeatures): array
    {
        $labels = [];

        foreach (PlanFeature::cases() as $feature) {
            if (in_array($feature->value, $enabledFeatures, true)) {
                $labels[] = $feature->label();
            }
        }

        return $labels;
    }

    /**
     * @param  list<mixed>  $keys
     * @return list<string>
     */
    protected function filterValidFeatureKeys(array $keys): array
    {
        $valid = PlanFeature::values();

        return array_values(array_filter(
            array_map(fn (mixed $key) => is_string($key) ? $key : '', $keys),
            fn (string $key) => in_array($key, $valid, true),
        ));
    }

    /**
     * @return array{trial_days: int, plans: array<string, array<string, mixed>>}
     */
    protected function settings(): array
    {
        if (! Schema::hasTable('platform_settings')) {
            return $this->defaultSettings();
        }

        /** @var array{trial_days?: int, plans?: array<string, array<string, mixed>>} $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $row = PlatformSetting::query()->find(self::SETTINGS_KEY);

            if ($row === null) {
                return $this->defaultSettings();
            }

            return is_array($row->value) ? $row->value : $this->defaultSettings();
        });

        return $settings;
    }

    /**
     * @return array{trial_days: int, plans: array<string, array<string, mixed>>}
     */
    protected function defaultSettings(): array
    {
        return [
            'trial_days' => (int) config('billing.trial_days', 14),
            'plans' => [],
        ];
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     employee_limit: int|null,
     *     payroll: bool,
     *     price_label: string,
     *     monthly_price: int|null,
     *     stripe_price_id: string|null,
     *     paypal_plan_id: string|null,
     *     enabled_features: list<string>
     * }>
     */
    protected function defaultPlans(): array
    {
        /** @var array<string, array<string, mixed>> $plans */
        $plans = config('billing.plans', []);

        $normalized = [];

        foreach ($plans as $key => $plan) {
            $enabledFeatures = $this->filterValidFeatureKeys($plan['enabled_features'] ?? []);

            if ($enabledFeatures === [] && is_array($plan['features'] ?? null)) {
                $enabledFeatures = $this->legacyFeaturesToEnabled($plan['features'], (bool) ($plan['payroll'] ?? false));
            }

            if ($key === SubscriptionPlan::Enterprise->value) {
                $enabledFeatures = PlanFeature::values();
            }

            if (! in_array(PlanFeature::Employees->value, $enabledFeatures, true)) {
                $enabledFeatures[] = PlanFeature::Employees->value;
            }

            $stripePriceId = $plan['stripe_price_id'] ?? null;
            $paypalPlanId = $plan['paypal_plan_id'] ?? null;

            $normalized[$key] = [
                'name' => (string) ($plan['name'] ?? ucfirst($key)),
                'employee_limit' => array_key_exists('employee_limit', $plan) && $plan['employee_limit'] !== null
                    ? (int) $plan['employee_limit']
                    : null,
                'payroll' => in_array(PlanFeature::Payroll->value, $enabledFeatures, true),
                'price_label' => (string) ($plan['price_label'] ?? ''),
                'monthly_price' => array_key_exists('monthly_price', $plan) && $plan['monthly_price'] !== null
                    ? (int) $plan['monthly_price']
                    : null,
                'stripe_price_id' => is_string($stripePriceId) && $stripePriceId !== '' ? $stripePriceId : null,
                'paypal_plan_id' => is_string($paypalPlanId) && $paypalPlanId !== '' ? $paypalPlanId : null,
                'enabled_features' => array_values(array_unique($enabledFeatures)),
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $legacyFeatures
     * @return list<string>
     */
    protected function legacyFeaturesToEnabled(array $legacyFeatures, bool $payroll): array
    {
        $enabled = [
            PlanFeature::Employees->value,
            PlanFeature::Leave->value,
            PlanFeature::Documents->value,
            PlanFeature::TeamInvitations->value,
            PlanFeature::Departments->value,
        ];

        foreach ($legacyFeatures as $line) {
            $line = strtolower((string) $line);

            if (str_contains($line, 'payroll') || str_contains($line, 'payslip') || str_contains($line, 'tax export')) {
                $enabled[] = PlanFeature::Payroll->value;
                $enabled[] = PlanFeature::Reports->value;
            }

            if (str_contains($line, 'report')) {
                $enabled[] = PlanFeature::Reports->value;
            }
        }

        if ($payroll) {
            $enabled[] = PlanFeature::Payroll->value;
        }

        return $this->filterValidFeatureKeys($enabled);
    }
}
