<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Services\EmployeeProfileService;
use App\Services\LeaveAuthorizationService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveRequestService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveAuthorizationService $leaveAuth,
        protected EmployeeProfileService $profiles,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();

        $query = $this->leaveAuth->scopeVisibleTo(
            LeaveRequest::query()
                ->with(['employee', 'leaveType', 'submittedBy'])
                ->orderByDesc('created_at'),
            $user,
            $organization,
        );

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, array_column(LeaveRequestStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($this->leaveAuth->canViewAll($user, $organization) && ($employeeId = $request->integer('employee_id'))) {
            $query->where('employee_id', $employeeId);
        }

        if ($leaveTypeId = $request->integer('leave_type_id')) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        $linkedEmployee = $this->profiles->employeeFor($user, $organization);
        $canCreateForOthers = $this->leaveAuth->canCreateForOthers($user, $organization);
        $canRequestOwn = $this->leaveAuth->canRequestOwn($user, $organization);

        return view('app.leave.index', [
            'organization' => $organization,
            'leaveRequests' => $query->paginate(20)->withQueryString(),
            'employees' => $canCreateForOthers
                ? Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                : collect(),
            'leaveTypes' => LeaveType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => LeaveRequestStatus::cases(),
            'canCreate' => $user->can('create', LeaveRequest::class),
            'canCreateForOthers' => $canCreateForOthers,
            'canRequestOwn' => $canRequestOwn,
            'linkedEmployee' => $linkedEmployee,
            'needsProfileLink' => $user->roleIn($organization)?->canRequestOwnLeave()
                && $linkedEmployee === null,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LeaveRequest::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();
        $canCreateForOthers = $this->leaveAuth->canCreateForOthers($user, $organization);
        $linkedEmployee = $this->profiles->employeeFor($user, $organization);

        return view('app.leave.create', [
            'organization' => $organization,
            'employees' => $canCreateForOthers
                ? Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                : collect(),
            'leaveTypes' => LeaveType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'linkedEmployee' => $linkedEmployee,
            'isSelfService' => ! $canCreateForOthers,
        ]);
    }

    public function store(StoreLeaveRequestRequest $request, LeaveRequestService $leaveRequests): RedirectResponse
    {
        $leaveRequest = $leaveRequests->create($request->validated(), $request->user());

        return redirect()
            ->route('leave.show', $leaveRequest)
            ->with('status', 'Leave request submitted for approval.');
    }

    public function show(Organization $organization, LeaveRequest $leaveRequest, LeaveBalanceService $balances): View
    {
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load(['employee', 'leaveType', 'submittedBy', 'reviewedBy']);

        $balance = $balances->balanceFor(
            $leaveRequest->employee,
            $leaveRequest->leaveType,
            (int) $leaveRequest->start_date->year,
        );

        $organization = CurrentOrganization::check();

        return view('app.leave.show', [
            'organization' => $organization,
            'leaveRequest' => $leaveRequest,
            'balance' => $balance,
            'canApprove' => auth()->user()->can('approve', $leaveRequest),
            'canCancel' => auth()->user()->can('cancel', $leaveRequest),
            'isOwnRequest' => $this->leaveAuth->ownsRequest(auth()->user(), $leaveRequest),
        ]);
    }

    public function approve(Organization $organization, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequests): RedirectResponse
    {
        $this->authorize('approve', $leaveRequest);

        $leaveRequests->approve($leaveRequest, auth()->user());

        return redirect()
            ->route('leave.show', $leaveRequest)
            ->with('status', 'Leave request approved.');
    }

    public function reject(RejectLeaveRequestRequest $request, Organization $organization, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequests): RedirectResponse
    {
        $leaveRequests->reject($leaveRequest, $request->user(), $request->validated('rejection_reason'));

        return redirect()
            ->route('leave.show', $leaveRequest)
            ->with('status', 'Leave request rejected.');
    }

    public function cancel(Organization $organization, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequests): RedirectResponse
    {
        $this->authorize('cancel', $leaveRequest);

        $leaveRequests->cancel($leaveRequest);

        return redirect()
            ->route('leave.index')
            ->with('status', 'Leave request cancelled.');
    }
}
