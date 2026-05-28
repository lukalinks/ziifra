<?php

namespace App\Http\Requests\Concerns;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\CompensationType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Support\CurrentOrganization;
use Illuminate\Validation\Rule;

trait ValidatesEmployeeFields
{
    /**
     * @return array<string, mixed>
     */
    protected function employeeFieldRules(?Employee $employee = null): array
    {
        $organizationId = CurrentOrganization::check()->id;

        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_code')
                    ->where('organization_id', $organizationId)
                    ->ignore($employee?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')
                    ->where('organization_id', $organizationId)
                    ->ignore($employee?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organizationId),
            ],
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where('organization_id', $organizationId),
            ],
            'manager_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
                Rule::notIn($employee ? [$employee->id] : []),
            ],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'start_date' => ['nullable', 'date'],
            'gross_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'monthly_allowances' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'compensation_type' => ['nullable', Rule::enum(CompensationType::class)],
            'fixed_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fixed_hourly_currency' => ['nullable', 'string', 'size:3'],
            'fixed_monthly_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fixed_salary_currency' => ['nullable', 'string', 'size:3'],
            'allowance_templates' => ['sometimes', 'nullable', 'array'],
            'allowance_templates.*.label' => ['nullable', 'string', 'max:255'],
            'allowance_templates.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'allowance_templates.*.tax_treatment' => ['nullable', Rule::enum(AllowanceTaxTreatment::class)],
            'user_id' => [
                'nullable',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId),
                Rule::unique('employees', 'user_id')
                    ->where('organization_id', $organizationId)
                    ->ignore($employee?->id),
            ],
            'project_ids' => ['sometimes', 'nullable', 'array'],
            'project_ids.*' => [
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
