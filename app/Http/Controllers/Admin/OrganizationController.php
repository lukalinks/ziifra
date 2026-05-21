<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\AdminAuditService;
use App\Services\AdminPlatformService;
use App\Services\BillingConfigurationService;
use App\Services\OrganizationBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request, OrganizationBillingService $billing, AdminPlatformService $platform, BillingConfigurationService $billingConfig): View
    {
        $query = Organization::query()
            ->with('owner')
            ->withCount([
                'employees as active_employees_count' => fn ($q) => $q->where('employment_status', '!=', 'terminated'),
                'users as members_count',
            ])
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $status = $request->string('status')->toString();

        if ($status === 'suspended') {
            $query->whereNotNull('suspended_at');
        } elseif ($status === 'trial_expired') {
            $trialDays = $billingConfig->trialDays();
            $query->whereNull('suspended_at')
                ->where('plan', SubscriptionPlan::Trial->value)
                ->where(function ($q) use ($trialDays): void {
                    $q->where('trial_ends_at', '<', now())
                        ->orWhere(function ($inner) use ($trialDays): void {
                            $inner->whereNull('trial_ends_at')
                                ->where('created_at', '<', now()->subDays($trialDays));
                        });
                });
        }

        if ($plan = $request->string('plan')->toString()) {
            $query->where('plan', $plan);
        }

        return view('admin.organizations.index', [
            'organizations' => $query->paginate(config('admin.organizations_per_page', 25))->withQueryString(),
            'billing' => $billing,
            'platform' => $platform,
            'plans' => SubscriptionPlan::cases(),
        ]);
    }

    public function show(Organization $organization, OrganizationBillingService $billing, AdminPlatformService $platform): View
    {
        $organization->load([
            'owner',
            'users' => fn ($q) => $q->orderBy('name'),
        ]);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'billing' => $billing,
            'platform' => $platform,
            'plans' => SubscriptionPlan::cases(),
            'employeeCount' => $billing->activeEmployeeCount($organization),
            'auditLogs' => $organization->adminAuditLogs()
                ->with(['admin', 'targetUser'])
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function updatePlan(Request $request, Organization $organization, AdminAuditService $audit, BillingConfigurationService $billingConfig): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ]);

        $previous = $organization->plan;
        $organization->update([
            'plan' => $validated['plan'],
            'trial_ends_at' => $validated['plan'] === SubscriptionPlan::Trial->value
                ? ($organization->trial_ends_at ?? now()->addDays($billingConfig->trialDays()))
                : null,
        ]);

        $audit->log(
            $request->user(),
            'organization.plan_changed',
            $organization,
            metadata: ['from' => $previous, 'to' => $validated['plan']],
            request: $request,
        );

        return back()->with('status', __('billing.plan_updated'));
    }

    public function updateTrial(
        Request $request,
        Organization $organization,
        AdminAuditService $audit,
        AdminPlatformService $platform,
    ): RedirectResponse {
        $validated = $request->validate([
            'trial_ends_at' => ['required', 'date', 'after:today'],
        ]);

        $endsAt = $platform->parseTrialEndsAt($validated['trial_ends_at']);
        $previous = $organization->trial_ends_at?->toIso8601String();

        $organization->update([
            'plan' => SubscriptionPlan::Trial->value,
            'trial_ends_at' => $endsAt,
        ]);

        $audit->log(
            $request->user(),
            'organization.trial_extended',
            $organization,
            metadata: [
                'from' => $previous,
                'to' => $endsAt->toIso8601String(),
            ],
            request: $request,
        );

        return back()->with('status', __('admin.organizations.trial_extended'));
    }

    public function suspend(Request $request, Organization $organization, AdminAuditService $audit): RedirectResponse
    {
        if ($organization->suspended_at === null) {
            $organization->update(['suspended_at' => now()]);

            $audit->log($request->user(), 'organization.suspended', $organization, request: $request);
        }

        return back()->with('status', __('billing.suspended_success'));
    }

    public function unsuspend(Request $request, Organization $organization, AdminAuditService $audit): RedirectResponse
    {
        if ($organization->suspended_at !== null) {
            $organization->update(['suspended_at' => null]);

            $audit->log($request->user(), 'organization.unsuspended', $organization, request: $request);
        }

        return back()->with('status', __('billing.unsuspended_success'));
    }
}
