<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Services\EmployeeDocumentService;
use App\Support\EmployeeDocumentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(
        StoreEmployeeDocumentRequest $request,
        Organization $organization,
        Employee $employee,
        EmployeeDocumentService $documents,
    ): RedirectResponse {
        $documents->store(
            $employee,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('documents.uploaded'));
    }

    public function download(
        Organization $organization,
        Employee $employee,
        EmployeeDocument $document,
    ): StreamedResponse {
        $this->authorize('view', $document);

        abort_unless($document->employee_id === $employee->id, 404);
        abort_unless(EmployeeDocumentStorage::exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename,
        );
    }

    public function destroy(
        Organization $organization,
        Employee $employee,
        EmployeeDocument $document,
        EmployeeDocumentService $documents,
    ): RedirectResponse {
        $this->authorize('delete', $document);

        abort_unless($document->employee_id === $employee->id, 404);

        $documents->delete($document);

        $redirect = request()->string('redirect')->toString() === 'documents'
            ? redirect()->route('documents.index', array_filter([
                'folder' => request()->integer('folder') ?: null,
                'type' => request()->string('type')->toString() ?: null,
                'search' => request()->string('search')->toString() ?: null,
                'employee_id' => request()->integer('employee_id') ?: null,
                'expiry' => request()->string('expiry')->toString() ?: null,
            ]))
            : redirect()->route('employees.show', $employee);

        return $redirect->with('status', __('documents.deleted'));
    }
}
