<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = \App\Support\CurrentOrganization::get();
        $role = $this->user()?->roleIn($organization);

        return $role?->canManageEmployees() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = \App\Support\CurrentOrganization::id();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'save_to_documents' => ['sometimes', 'boolean'],
        ];
    }
}
