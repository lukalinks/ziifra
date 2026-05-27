<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\DocumentFolder::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::id();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('document_folders', 'name')->where('organization_id', $organizationId),
            ],
        ];
    }
}
