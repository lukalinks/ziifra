<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Support\EmployeeDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocumentService
{
    /**
     * @param  array{type: string, title: string, document_folder_id?: int|null, expires_at?: string|null, notes?: string|null}  $data
     */
    public function store(Employee $employee, UploadedFile $file, array $data, User $uploadedBy): EmployeeDocument
    {
        $stored = EmployeeDocumentStorage::store($employee, $file);

        return EmployeeDocument::query()->create([
            'organization_id' => $employee->organization_id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $uploadedBy->id,
            'document_folder_id' => $data['document_folder_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function delete(EmployeeDocument $document): void
    {
        $document->delete();
    }

    /**
     * @param  array{type: string, title: string, expires_at?: string|null, notes?: string|null}  $data
     */
    public function storeFromBinary(
        Employee $employee,
        string $binary,
        string $originalFilename,
        array $data,
        User $uploadedBy,
    ): EmployeeDocument {
        $directory = sprintf(
            'organizations/%d/employees/%d/documents',
            $employee->organization_id,
            $employee->id,
        );

        $path = $directory.'/'.Str::uuid()->toString().'.pdf';
        Storage::disk('local')->put($path, $binary);

        return EmployeeDocument::query()->create([
            'organization_id' => $employee->organization_id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $uploadedBy->id,
            'document_folder_id' => $data['document_folder_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $path,
            'original_filename' => $originalFilename,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
