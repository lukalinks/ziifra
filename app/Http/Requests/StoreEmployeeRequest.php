<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEmployeeCustomFields;
use App\Http\Requests\Concerns\ValidatesEmployeeFields;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    use ValidatesEmployeeCustomFields;
    use ValidatesEmployeeFields;

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employee::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            $this->employeeFieldRules(),
            $this->customFieldRules(),
        );
    }

    protected function prepareForValidation(): void
    {
        $organization = CurrentOrganization::check();

        $this->merge([
            'first_name' => $this->filled('first_name') ? $this->input('first_name') : __('employees.unnamed'),
            'last_name' => $this->input('last_name') ?? '',
            'employment_type' => $this->filled('employment_type')
                ? $this->input('employment_type')
                : ($organization->default_employment_type ?? \App\Enums\EmploymentType::FullTime->value),
            'employment_status' => $this->filled('employment_status')
                ? $this->input('employment_status')
                : \App\Enums\EmploymentStatus::Active->value,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $organization = CurrentOrganization::get();

            if ($organization === null) {
                return;
            }

            $billing = app(OrganizationBillingService::class);

            if (! $billing->canAddEmployee($organization)) {
                $plan = $billing->plan($organization)->label();
                $limit = $billing->employeeLimit($organization);

                $validator->errors()->add(
                    'first_name',
                    $limit !== null
                        ? __('billing.employee_limit', ['plan' => $plan, 'limit' => $limit])
                        : __('billing.trial_expired'),
                );
            }
        });
    }
}
