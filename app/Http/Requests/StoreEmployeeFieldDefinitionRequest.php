<?php

namespace App\Http\Requests;

use App\Enums\CustomFieldType;
use App\Models\EmployeeFieldDefinition;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeFieldDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EmployeeFieldDefinition::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::check()->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_field_definitions', 'name')->where('organization_id', $organizationId),
            ],
            'type' => ['required', Rule::enum(CustomFieldType::class)],
            'options' => [
                Rule::requiredIf(fn () => $this->input('type') === CustomFieldType::Select->value),
                'nullable',
                'string',
                'max:1000',
            ],
            'is_required' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_required' => $this->boolean('is_required'),
        ]);
    }
}
