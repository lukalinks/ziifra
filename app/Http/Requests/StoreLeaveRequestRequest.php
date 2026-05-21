<?php

namespace App\Http\Requests;

use App\Services\EmployeeProfileService;
use App\Services\LeaveAuthorizationService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\LeaveRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = CurrentOrganization::check();
        $organizationId = $organization->id;
        $leaveAuth = app(LeaveAuthorizationService::class);

        $rules = [
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('organization_id', $organizationId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($leaveAuth->canCreateForOthers($this->user(), $organization)) {
            $rules['employee_id'] = [
                'required',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $organization = CurrentOrganization::get();

        if ($organization === null) {
            return;
        }

        $employee = app(EmployeeProfileService::class)->employeeFor($this->user(), $organization);

        if ($employee !== null && ! app(LeaveAuthorizationService::class)->canCreateForOthers($this->user(), $organization)) {
            $this->merge(['employee_id' => $employee->id]);
        }
    }
}
