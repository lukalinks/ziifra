<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseClaimStatus;
use App\Http\Requests\RejectExpenseClaimRequest;
use App\Http\Requests\ScanExpenseReceiptRequest;
use App\Http\Requests\StoreExpenseClaimRequest;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Organization;
use App\Services\ExpenseAuthorizationService;
use App\Services\ExpenseClaimService;
use App\Services\EmployeeProfileService;
use App\Services\ReceiptExtractionService;
use App\Support\CurrentOrganization;
use App\Support\ExpenseReceiptStorage;
use App\Exceptions\ReceiptExtractionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseClaimController extends Controller
{
    public function __construct(
        protected ExpenseAuthorizationService $expenseAuth,
        protected EmployeeProfileService $profiles,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();

        $query = $this->expenseAuth->scopeVisibleTo(
            ExpenseClaim::query()
                ->with(['employee', 'submittedBy'])
                ->orderByDesc('expense_date')
                ->orderByDesc('id'),
            $user,
            $organization,
        );

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, array_column(ExpenseClaimStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($this->expenseAuth->canViewAll($user, $organization) && ($employeeId = $request->integer('employee_id'))) {
            $query->where('employee_id', $employeeId);
        }

        return view('app.expenses.index', [
            'organization' => $organization,
            'claims' => $query->paginate(20)->withQueryString(),
            'employees' => $this->expenseAuth->canCreateForOthers($user, $organization)
                ? Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                : collect(),
            'statuses' => ExpenseClaimStatus::cases(),
            'canCreate' => $user->can('create', ExpenseClaim::class),
            'canCreateForOthers' => $this->expenseAuth->canCreateForOthers($user, $organization),
            'canSubmitOwn' => $this->expenseAuth->canSubmitOwn($user, $organization),
            'needsProfileLink' => $user->roleIn($organization)?->canRequestOwnLeave()
                && $this->profiles->employeeFor($user, $organization) === null,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ExpenseClaim::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();

        return view('app.expenses.create', [
            'organization' => $organization,
            'employees' => $this->expenseAuth->canCreateForOthers($user, $organization)
                ? Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                : collect(),
            'canCreateForOthers' => $this->expenseAuth->canCreateForOthers($user, $organization),
            'linkedEmployee' => $this->profiles->employeeFor($user, $organization),
            'receiptScanAvailable' => app(ReceiptExtractionService::class)->isAvailable(),
        ]);
    }

    public function scanReceipt(
        ScanExpenseReceiptRequest $request,
        ReceiptExtractionService $scanner,
    ): JsonResponse {
        $organization = CurrentOrganization::check();

        if (! $scanner->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => __('expenses.scan_not_configured'),
            ], 503);
        }

        try {
            $extracted = $scanner->extract($request->file('receipt'), $organization);
        } catch (ReceiptExtractionException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $extracted->toArray(),
            'message' => __('expenses.scan_success'),
        ]);
    }

    public function store(StoreExpenseClaimRequest $request, ExpenseClaimService $expenses): RedirectResponse
    {
        $claim = $expenses->create(
            $request->validated(),
            $request->user(),
            $request->file('receipt'),
        );

        return redirect()
            ->to($claim->workspaceRoute('expenses.show'))
            ->with('status', __('expenses.submitted'));
    }

    public function show(Organization $organization, ExpenseClaim $expenseClaim): View
    {
        $this->authorize('view', $expenseClaim);

        $expenseClaim->load(['employee', 'submittedBy', 'reviewedBy']);

        return view('app.expenses.show', [
            'organization' => CurrentOrganization::check(),
            'claim' => $expenseClaim,
            'canApprove' => auth()->user()->can('approve', $expenseClaim),
        ]);
    }

    public function approve(
        Organization $organization,
        ExpenseClaim $expenseClaim,
        ExpenseClaimService $expenses,
    ): RedirectResponse {
        $this->authorize('approve', $expenseClaim);

        $expenses->approve($expenseClaim, auth()->user());

        return redirect()
            ->to($expenseClaim->workspaceRoute('expenses.show'))
            ->with('status', __('expenses.approved'));
    }

    public function reject(
        RejectExpenseClaimRequest $request,
        Organization $organization,
        ExpenseClaim $expenseClaim,
        ExpenseClaimService $expenses,
    ): RedirectResponse {
        $expenses->reject($expenseClaim, $request->user(), $request->validated('rejection_reason'));

        return redirect()
            ->to($expenseClaim->workspaceRoute('expenses.show'))
            ->with('status', __('expenses.rejected'));
    }

    public function cancel(
        Organization $organization,
        ExpenseClaim $expenseClaim,
        ExpenseClaimService $expenses,
    ): RedirectResponse {
        $this->authorize('cancel', $expenseClaim);

        $expenses->cancel($expenseClaim);

        return redirect()
            ->route('expenses.index')
            ->with('status', __('expenses.cancelled'));
    }

    public function downloadReceipt(Organization $organization, ExpenseClaim $expenseClaim): StreamedResponse
    {
        $this->authorize('view', $expenseClaim);

        abort_unless($expenseClaim->hasReceipt(), 404);

        return Storage::disk('local')->download(
            $expenseClaim->receipt_path,
            $expenseClaim->original_filename ?? 'receipt',
        );
    }
}
