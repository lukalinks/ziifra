<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Models\Employee;
use App\Services\ExpenseAuthorizationService;
use App\Support\CurrentOrganization;
use App\Support\ExpenseReceiptStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ExpenseClaim::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = CurrentOrganization::check();
        $user = $this->user();
        $expenseAuth = app(ExpenseAuthorizationService::class);

        $rules = [
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => [
                'nullable',
                File::types(ExpenseReceiptStorage::ALLOWED_MIMES)
                    ->max(ExpenseReceiptStorage::MAX_KILOBYTES),
            ],
        ];

        if ($expenseAuth->canCreateForOthers($user, $organization)) {
            $rules['employee_id'] = [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organization->id),
            ];
        }

        return $rules;
    }
}
