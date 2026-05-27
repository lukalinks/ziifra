<?php

namespace App\Http\Requests;

use App\Enums\ProjectDocumentCategory;
use App\Models\Project;
use App\Support\CurrentOrganization;
use App\Support\ProjectDocumentStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProjectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ProjectDocument::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::id();

        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'category' => ['required', Rule::enum(ProjectDocumentCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                File::types(ProjectDocumentStorage::ALLOWED_MIMES)
                    ->max(ProjectDocumentStorage::MAX_KILOBYTES),
            ],
        ];
    }
}
