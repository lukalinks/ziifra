<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Support\CurrentOrganization;
use Illuminate\Validation\Rule;

class StoreDocumentFromIndexRequest extends StoreEmployeeDocumentRequest
{
    public function authorize(): bool
    {
        $employee = Employee::query()->find($this->integer('employee_id'));

        return $employee !== null && $this->user()->can('update', $employee);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::id();

        return array_merge(parent::rules(), [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
        ]);
    }
}
