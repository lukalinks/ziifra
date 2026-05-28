<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Project;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollTimeUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::check()->id;

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $organizationId)],
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0', 'max:24'],
        ];
    }
}
