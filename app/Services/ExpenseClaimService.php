<?php

namespace App\Services;

use App\Enums\ExpenseClaimStatus;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\ExpenseReceiptStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ExpenseClaimService
{
    public function __construct(
        protected ExpenseAuthorizationService $expenseAuth,
        protected EmployeeProfileService $profiles,
        protected InAppNotificationService $inApp,
    ) {}

    /**
     * @param  array{
     *     employee_id?: int,
     *     category: string,
     *     title: string,
     *     amount: float|string,
     *     expense_date: string,
     *     notes?: string|null,
     * }  $data
     */
    public function create(array $data, User $submittedBy, ?UploadedFile $receipt = null): ExpenseClaim
    {
        $organization = CurrentOrganization::check();
        $employee = $this->resolveEmployee($data, $submittedBy, $organization);

        $claim = ExpenseClaim::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'submitted_by_user_id' => $submittedBy->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'currency' => $organization->currency ?? 'EUR',
            'expense_date' => $data['expense_date'],
            'status' => ExpenseClaimStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($receipt !== null) {
            $this->attachReceipt($claim, $employee, $receipt);
        }

        $this->inApp->expenseSubmitted($claim->load(['employee', 'organization', 'submittedBy']));

        return $claim->load(['employee', 'submittedBy']);
    }

    public function approve(ExpenseClaim $claim, User $reviewer): ExpenseClaim
    {
        $claim->update([
            'status' => ExpenseClaimStatus::Approved,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $fresh = $claim->fresh(['employee', 'reviewedBy', 'organization']);
        $this->inApp->expenseReviewed($fresh, $reviewer);

        return $fresh;
    }

    public function reject(ExpenseClaim $claim, User $reviewer, string $reason): ExpenseClaim
    {
        $claim->update([
            'status' => ExpenseClaimStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $fresh = $claim->fresh(['employee', 'reviewedBy', 'organization']);
        $this->inApp->expenseReviewed($fresh, $reviewer);

        return $fresh;
    }

    public function cancel(ExpenseClaim $claim): ExpenseClaim
    {
        if ($claim->status !== ExpenseClaimStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending claims can be cancelled.',
            ]);
        }

        $claim->update([
            'status' => ExpenseClaimStatus::Cancelled,
        ]);

        return $claim->fresh();
    }

    /**
     * @param  array{employee_id?: int, category: string, title: string, amount: float|string, expense_date: string, notes?: string|null}  $data
     */
    protected function resolveEmployee(array $data, User $submittedBy, Organization $organization): Employee
    {
        $role = $submittedBy->roleIn($organization);

        if ($this->expenseAuth->canSubmitOwn($submittedBy, $organization)
            && ! $this->expenseAuth->canCreateForOthers($submittedBy, $organization)) {
            $employee = $this->profiles->employeeFor($submittedBy, $organization);

            if ($employee === null) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Your account is not linked to an employee profile. Contact HR.',
                ]);
            }

            return $employee;
        }

        return Employee::query()->findOrFail($data['employee_id'] ?? 0);
    }

    protected function attachReceipt(ExpenseClaim $claim, Employee $employee, UploadedFile $receipt): void
    {
        $stored = ExpenseReceiptStorage::store($employee, $receipt);

        $claim->update([
            'receipt_path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
        ]);
    }
}
