<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Project;
use App\Services\EmployeeProfileService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollTimeUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $organization = CurrentOrganization::check();

        if ($user->can('create', Employee::class)) {
            return true;
        }

        $linked = app(EmployeeProfileService::class)->employeeFor($user, $organization);

        return $linked !== null
            && (int) $this->input('employee_id') === $linked->id;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->user()->can('create', Employee::class)) {
                return;
            }

            $organization = CurrentOrganization::check();
            $linked = app(EmployeeProfileService::class)->employeeFor($this->user(), $organization);
            $projectId = (int) $this->input('project_id');

            if ($linked === null) {
                $validator->errors()->add('employee_id', __('my_hours.not_linked'));

                return;
            }

            $isMember = Project::query()
                ->where('organization_id', $organization->id)
                ->whereKey($projectId)
                ->whereHas('members', fn ($q) => $q->whereKey($linked->id))
                ->exists();

            if (! $isMember) {
                $validator->errors()->add('project_id', __('my_hours.not_on_project'));
            }
        });
    }
}
