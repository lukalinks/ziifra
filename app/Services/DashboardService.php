<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\PayrollRunStatus;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected LeaveAuthorizationService $leaveAuth,
    ) {}

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function outToday(User $user, Organization $organization): Collection
    {
        $today = Carbon::today();

        return $this->scopedLeaveQuery($user, $organization)
            ->with(['employee', 'leaveType'])
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function upcomingLeave(User $user, Organization $organization, int $days = 14): Collection
    {
        $start = Carbon::tomorrow();
        $end = Carbon::today()->addDays($days);

        return $this->scopedLeaveQuery($user, $organization)
            ->with(['employee', 'leaveType'])
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('start_date', '>=', $start)
            ->whereDate('start_date', '<=', $end)
            ->orderBy('start_date')
            ->limit(10)
            ->get();
    }

    public function expiringDocumentCount(Organization $organization): int
    {
        return EmployeeDocument::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();
    }

    public function pendingLeaveCount(User $user, Organization $organization): int
    {
        return $this->scopedLeaveQuery($user, $organization)
            ->where('status', LeaveRequestStatus::Pending)
            ->count();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function pendingLeaveRequestsForUser(User $user, Organization $organization, int $limit = 8): Collection
    {
        return $this->scopedLeaveQuery($user, $organization)
            ->where('status', LeaveRequestStatus::Pending)
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return list<array{date: Carbon, label: string, short_label: string, count: int, is_today: bool}>
     */
    public function weekOutlook(User $user, Organization $organization, int $days = 7): array
    {
        $outlook = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = Carbon::today()->addDays($offset);
            $count = $this->scopedLeaveQuery($user, $organization)
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->count();

            $outlook[] = [
                'date' => $date,
                'label' => $date->format('D'),
                'short_label' => $date->format('j'),
                'count' => $count,
                'is_today' => $offset === 0,
            ];
        }

        return $outlook;
    }

    public function teamInOfficeCount(Organization $organization, int $outTodayCount): int
    {
        $active = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employment_status', EmploymentStatus::Active)
            ->count();

        return max(0, $active - $outTodayCount);
    }

    /**
     * @return Collection<int, Employee>
     */
    public function recentHires(Organization $organization, int $limit = 5): Collection
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employment_status', EmploymentStatus::Active)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '>=', now()->subDays(60))
            ->with('department')
            ->orderByDesc('start_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function expiringDocuments(Organization $organization, int $limit = 5): Collection
    {
        return EmployeeDocument::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->with('employee')
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{type: string, remaining: float, used: float, entitled: float}|null
     */
    public function myLeaveBalance(User $user, Organization $organization, LeaveBalanceService $balances): ?array
    {
        $employee = app(EmployeeProfileService::class)->employeeFor($user, $organization);

        if ($employee === null) {
            return null;
        }

        $leaveType = LeaveType::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('default_days_per_year')
            ->orderBy('name')
            ->first();

        if ($leaveType === null) {
            return null;
        }

        $balance = $balances->balanceFor($employee, $leaveType);

        return [
            'type' => $leaveType->name,
            'remaining' => $balance->remainingDays(),
            'used' => (float) $balance->used_days,
            'entitled' => (float) $balance->entitled_days,
        ];
    }

    public function latestDraftPayrollRun(Organization $organization): ?PayrollRun
    {
        return PayrollRun::query()
            ->where('organization_id', $organization->id)
            ->where('status', PayrollRunStatus::Draft)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function myLeaveRequests(User $user, Organization $organization, int $limit = 5): Collection
    {
        return $this->scopedLeaveQuery($user, $organization)
            ->with(['leaveType'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function newHiresThisMonth(Organization $organization): int
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employment_status', EmploymentStatus::Active)
            ->whereNotNull('start_date')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<LeaveRequest>
     */
    protected function scopedLeaveQuery(User $user, Organization $organization)
    {
        return $this->leaveAuth->scopeVisibleTo(
            LeaveRequest::query(),
            $user,
            $organization,
        );
    }
}
