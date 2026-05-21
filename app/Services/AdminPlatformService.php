<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\AdminAuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminPlatformService
{
    public function __construct(
        private OrganizationBillingService $billing,
    ) {}

    public function trialExpiringSoonCount(int $withinDays = 7): int
    {
        $until = now()->addDays($withinDays);

        return Organization::query()
            ->where('plan', SubscriptionPlan::Trial->value)
            ->whereNull('suspended_at')
            ->get()
            ->filter(function (Organization $organization) use ($until): bool {
                $endsAt = $this->billing->trialEndsAt($organization);

                return $endsAt !== null
                    && $endsAt->isFuture()
                    && $endsAt->lte($until);
            })
            ->count();
    }

    public function paidWorkspaceCount(): int
    {
        return Organization::query()
            ->whereNull('suspended_at')
            ->where('plan', '!=', SubscriptionPlan::Trial->value)
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Organization>
     */
    public function recentOrganizations(int $limit = 8)
    {
        return Organization::query()
            ->with('owner')
            ->withCount([
                'employees as active_employees_count' => fn ($q) => $q->where('employment_status', '!=', 'terminated'),
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AdminAuditLog>
     */
    public function recentAuditLogs(int $limit = 10)
    {
        return AdminAuditLog::query()
            ->with(['admin', 'organization', 'targetUser'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function organizationStatusLabel(Organization $organization): string
    {
        if ($organization->suspended_at !== null) {
            return 'suspended';
        }

        if ($this->billing->trialExpired($organization)) {
            return 'trial_expired';
        }

        return 'active';
    }

    public function actionLabel(string $action): string
    {
        $key = 'admin.actions.'.$action;

        return __($key) !== $key ? __($key) : str_replace('.', ' ', $action);
    }

    public function parseTrialEndsAt(string $value): Carbon
    {
        return Carbon::parse($value)->endOfDay();
    }
}
