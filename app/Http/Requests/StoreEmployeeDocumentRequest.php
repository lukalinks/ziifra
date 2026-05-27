<?php

namespace App\Http\Requests;

use App\Enums\EmployeeDocumentType;
use App\Models\Employee;
use App\Support\CurrentOrganization;
use App\Support\EmployeeDocumentStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && $this->user()->can('update', $employee);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(EmployeeDocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                File::types(EmployeeDocumentStorage::ALLOWED_MIMES)
                    ->max(EmployeeDocumentStorage::MAX_KILOBYTES),
            ],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_folder_id' => [
                'nullable',
                'integer',
                Rule::exists('document_folders', 'id')->where('organization_id', CurrentOrganization::id()),
            ],
        ];
    }
}
