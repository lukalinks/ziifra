<?php

namespace App\Http\Requests;

use App\Enums\ProjectTaskPriority;
use App\Enums\ProjectTaskStatus;
use App\Models\Project;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()->can('update', $project);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::id();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ProjectTaskStatus::class)],
            'priority' => ['required', Rule::enum(ProjectTaskPriority::class)],
            'assigned_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
            'is_milestone' => ['boolean'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
