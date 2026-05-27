<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DailyHoursService;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\DashboardService;
use App\Services\EmployeeProfileService;
use App\Services\LeaveAuthorizationService;
use App\Services\LeaveBalanceService;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use App\Support\DashboardQuickActions;
use App\Support\WorkspaceNavigation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        DashboardService $dashboard,
        AdminDashboardService $adminDashboard,
        LeaveAuthorizationService $leaveAuth,
        LeaveBalanceService $leaveBalances,
        OrganizationBillingService $billing,
        EmployeeProfileService $profiles,
    ): View {
        $organization = CurrentOrganization::check();
        $user = auth()->user();
        $role = $user->roleIn($organization);

        if ($role?->usesAdminDashboard()) {
            return view('app.dashboard.admin', $this->adminPayload(
                $organization,
                $user,
                $role,
                $dashboard,
                $adminDashboard,
                $leaveAuth,
                $leaveBalances,
                $billing,
            ));
        }

        if ($role?->usesTeamDashboard()) {
            return view('app.dashboard.team', $this->teamPayload(
                $organization,
                $user,
                $dashboard,
                $leaveAuth,
                $leaveBalances,
            ));
        }

        return view('app.dashboard.employee', $this->employeePayload(
            $organization,
            $user,
            $dashboard,
            $leaveAuth,
            $leaveBalances,
            $profiles,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function adminPayload(
        Organization $organization,
        User $user,
        OrganizationRole $role,
        DashboardService $dashboard,
        AdminDashboardService $adminDashboard,
        LeaveAuthorizationService $leaveAuth,
        LeaveBalanceService $leaveBalances,
        OrganizationBillingService $billing,
    ): array {
        $activeEmployeeCount = Employee::query()
            ->where('employment_status', EmploymentStatus::Active)
            ->count();

        $employeeLimit = $billing->employeeLimit($organization);
        $outToday = $dashboard->outToday($user, $organization);
        $pendingLeaveCount = $dashboard->pendingLeaveCount($user, $organization);
        $expiringDocumentCount = $dashboard->expiringDocumentCount($organization);
        $pendingInvitations = $adminDashboard->pendingInvitationsCount($organization);
        $employeesMissingLogin = $adminDashboard->employeesMissingLoginCount($organization);
        $teamUserCount = $organization->users()->count();
        $hasPayroll = $billing->hasPayroll($organization);
        $draftPayrollRun = $hasPayroll ? $dashboard->latestDraftPayrollRun($organization) : null;
        $canManageOrganization = $role->canManageOrganization();
        $trialDaysRemaining = $billing->isOnTrial($organization)
            ? $billing->trialDaysRemaining($organization)
            : null;
        $hoursStats = app(DailyHoursService::class)->organizationStats($organization->id);

        return [
            'organization' => $organization,
            'user' => $user,
            'role' => $role,
            'roleLabel' => match ($role) {
                OrganizationRole::Owner => __('admin_dashboard.role_owner'),
                OrganizationRole::Admin => __('admin_dashboard.role_admin'),
                default => __('admin_dashboard.role_hr'),
            },
            'greeting' => $this->greeting(),
            'hasPayroll' => $hasPayroll,
            'activeEmployeeCount' => $activeEmployeeCount,
            'employeeLimit' => $employeeLimit,
            'employeeUsagePercent' => $employeeLimit
                ? min(100, (int) round(($activeEmployeeCount / $employeeLimit) * 100))
                : null,
            'departmentCount' => Department::query()->count(),
            'pendingLeaveCount' => $pendingLeaveCount,
            'pendingLeaveRequests' => $adminDashboard->pendingLeaveRequests($organization),
            'expiringDocumentCount' => $expiringDocumentCount,
            'expiringDocuments' => $dashboard->expiringDocuments($organization),
            'pendingInvitations' => $pendingInvitations,
            'employeesMissingLogin' => $employeesMissingLogin,
            'approvedLeaveDaysMonth' => $adminDashboard->approvedLeaveDaysThisMonth($organization),
            'teamUserCount' => $teamUserCount,
            'planName' => $billing->planDetails($organization)['name'] ?? null,
            'trialDaysRemaining' => $trialDaysRemaining,
            'canManageOrganization' => $canManageOrganization,
            'canManageBilling' => $role->canManageBilling(),
            'outToday' => $outToday,
            'upcomingLeave' => $dashboard->upcomingLeave($user, $organization),
            'weekOutlook' => $dashboard->weekOutlook($user, $organization),
            'teamInOfficeCount' => $dashboard->teamInOfficeCount($organization, $outToday->count()),
            'newHiresThisMonth' => $dashboard->newHiresThisMonth($organization),
            'recentHires' => $dashboard->recentHires($organization),
            'draftPayrollRun' => $draftPayrollRun,
            'activeProjectsCount' => Project::query()->where('status', \App\Enums\ProjectStatus::Active)->count(),
            'hoursThisMonth' => $hoursStats['total_hours'],
            'pendingHoursApprovals' => $hoursStats['pending_count'],
            'currentYear' => (int) now()->year,
            'priorityAlerts' => $adminDashboard->priorityAlerts(
                $organization,
                $pendingLeaveCount,
                $expiringDocumentCount,
                $pendingInvitations,
                $employeesMissingLogin,
                $trialDaysRemaining,
                $role->canManageBilling(),
                $draftPayrollRun !== null,
            ),
            'setupChecklist' => $adminDashboard->setupChecklist(
                $activeEmployeeCount,
                $teamUserCount,
            ),
            'leaveTrendChart' => $adminDashboard->monthlyLeaveTrend($organization),
            'quickActions' => DashboardQuickActions::for(
                $user,
                $organization,
                true,
                true,
                false,
                $canManageOrganization,
                $hasPayroll,
            ),
            'canManageEmployees' => true,
            'canViewEmployees' => true,
            'myLeaveBalance' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function teamPayload(
        Organization $organization,
        User $user,
        DashboardService $dashboard,
        LeaveAuthorizationService $leaveAuth,
        LeaveBalanceService $leaveBalances,
    ): array {
        $outToday = $dashboard->outToday($user, $organization);

        return [
            'organization' => $organization,
            'user' => $user,
            'greeting' => $this->greeting(),
            'pendingLeaveCount' => $dashboard->pendingLeaveCount($user, $organization),
            'pendingLeaveRequests' => $dashboard->pendingLeaveRequestsForUser($user, $organization),
            'outToday' => $outToday,
            'upcomingLeave' => $dashboard->upcomingLeave($user, $organization),
            'weekOutlook' => $dashboard->weekOutlook($user, $organization),
            'myLeaveBalance' => $leaveAuth->canRequestOwn($user, $organization)
                ? $dashboard->myLeaveBalance($user, $organization, $leaveBalances)
                : null,
            'currentYear' => (int) now()->year,
            'quickActions' => DashboardQuickActions::for(
                $user,
                $organization,
                false,
                true,
                $leaveAuth->canRequestOwn($user, $organization),
                false,
                false,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeePayload(
        Organization $organization,
        User $user,
        DashboardService $dashboard,
        LeaveAuthorizationService $leaveAuth,
        LeaveBalanceService $leaveBalances,
        EmployeeProfileService $profiles,
    ): array {
        $hasProfile = $profiles->employeeFor($user, $organization) !== null;
        $employee = $hasProfile ? $profiles->employeeFor($user, $organization) : null;

        return [
            'organization' => $organization,
            'user' => $user,
            'greeting' => $this->greeting(),
            'hasEmployeeProfile' => $hasProfile,
            'myLeaveBalance' => $hasProfile
                ? $dashboard->myLeaveBalance($user, $organization, $leaveBalances)
                : null,
            'myLeaveRequests' => $hasProfile
                ? $dashboard->myLeaveRequests($user, $organization)
                : collect(),
            'pendingLeaveCount' => $employee
                ? LeaveRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', LeaveRequestStatus::Pending)
                    ->count()
                : 0,
            'portalShortcuts' => collect(app(WorkspaceNavigation::class)->flat($organization, $user))
                ->reject(fn (array $item) => $item['route'] === 'dashboard')
                ->values()
                ->all(),
            'currentYear' => (int) now()->year,
            'quickActions' => DashboardQuickActions::for(
                $user,
                $organization,
                false,
                false,
                $leaveAuth->canRequestOwn($user, $organization),
                false,
                false,
            ),
        ];
    }

    protected function greeting(): string
    {
        $timezone = CurrentOrganization::get()?->timezone ?? config('app.timezone');
        $hour = (int) now()->timezone($timezone)->format('G');

        if ($hour < 12) {
            return __('dashboard.greeting_morning');
        }

        if ($hour < 17) {
            return __('dashboard.greeting_afternoon');
        }

        return __('dashboard.greeting_evening');
    }
}
