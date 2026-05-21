<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\OrganizationContractTemplate $template */
        $template = $this->route('template');

        return $this->user()?->can('update', $template) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
