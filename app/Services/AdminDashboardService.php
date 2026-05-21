<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Invitation;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\Employee;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    /**
     * @return Collection<int, LeaveRequest>
     */
    public function pendingLeaveRequests(Organization $organization, int $limit = 8): Collection
    {
        return LeaveRequest::query()
            ->where('organization_id', $organization->id)
            ->where('status', LeaveRequestStatus::Pending)
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function pendingInvitationsCount(Organization $organization): int
    {
        return Invitation::query()
            ->where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->count();
    }

    public function employeesMissingLoginCount(Organization $organization): int
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employment_status', EmploymentStatus::Active)
            ->whereNotNull('email')
            ->whereNull('user_id')
            ->count();
    }

    public function approvedLeaveDaysThisMonth(Organization $organization): float
    {
        return (float) LeaveRequest::query()
            ->where('organization_id', $organization->id)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->sum('days');
    }

    /**
     * @return array{labels: list<string>, approved: list<float>, pending: list<int>}
     */
    public function monthlyLeaveTrend(Organization $organization, int $months = 6): array
    {
        $labels = [];
        $approved = [];
        $pending = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $approved[] = (float) LeaveRequest::query()
                ->where('organization_id', $organization->id)
                ->where('status', LeaveRequestStatus::Approved)
                ->whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year)
                ->sum('days');

            $pending[] = LeaveRequest::query()
                ->where('organization_id', $organization->id)
                ->where('status', LeaveRequestStatus::Pending)
                ->whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year)
                ->count();
        }

        return compact('labels', 'approved', 'pending');
    }

    /**
     * @return list<array{key: string, label: string, done: bool, href: string|null, priority: bool}>
     */
    public function setupChecklist(
        int $activeEmployeeCount,
        int $teamUserCount,
    ): array {
        $items = [];

        if ($teamUserCount <= 1) {
            $items[] = [
                'key' => 'team',
                'label' => __('admin_dashboard.checklist.invite_team'),
                'done' => false,
                'href' => route('team.index'),
                'priority' => true,
            ];
        }

        if ($activeEmployeeCount < 2) {
            $items[] = [
                'key' => 'employees',
                'label' => __('admin_dashboard.checklist.add_employees'),
                'done' => false,
                'href' => route('employees.create'),
                'priority' => false,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{label: string, count: int, href: string, variant: string}>
     */
    public function priorityAlerts(
        Organization $organization,
        int $pendingLeaveCount,
        int $expiringDocumentCount,
        int $pendingInvitations,
        int $employeesMissingLogin,
        ?int $trialDaysRemaining,
        bool $canManageBilling,
        bool $hasDraftPayroll,
    ): array {
        $alerts = [];

        if ($pendingLeaveCount > 0) {
            $alerts[] = [
                'label' => __('admin_dashboard.alert_pending_leave', ['count' => $pendingLeaveCount]),
                'count' => $pendingLeaveCount,
                'href' => route('leave.index', ['status' => 'pending']),
                'variant' => 'alert',
            ];
        }

        if ($expiringDocumentCount > 0) {
            $alerts[] = [
                'label' => __('admin_dashboard.alert_expiring_docs', ['count' => $expiringDocumentCount]),
                'count' => $expiringDocumentCount,
                'href' => route('employees.index'),
                'variant' => 'warn',
            ];
        }

        if ($employeesMissingLogin > 0) {
            $alerts[] = [
                'label' => __('admin_dashboard.alert_missing_login', ['count' => $employeesMissingLogin]),
                'count' => $employeesMissingLogin,
                'href' => route('employees.index', ['missing_login' => 1]),
                'variant' => 'default',
            ];
        }

        if ($pendingInvitations > 0) {
            $alerts[] = [
                'label' => __('admin_dashboard.alert_pending_invites', ['count' => $pendingInvitations]),
                'count' => $pendingInvitations,
                'href' => route('team.index'),
                'variant' => 'default',
            ];
        }

        if ($canManageBilling && $trialDaysRemaining !== null) {
            $alerts[] = [
                'label' => $trialDaysRemaining === 0
                    ? __('admin_dashboard.alert_trial_today')
                    : __('admin_dashboard.alert_trial', ['days' => $trialDaysRemaining]),
                'count' => $trialDaysRemaining,
                'href' => route('settings.billing'),
                'variant' => 'warn',
            ];
        }

        if ($hasDraftPayroll) {
            $alerts[] = [
                'label' => __('admin_dashboard.alert_payroll_draft'),
                'count' => 0,
                'href' => route('payroll.index'),
                'variant' => 'default',
            ];
        }

        return $alerts;
    }
}
