<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFromIndexRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\OrganizationContractTemplate;
use App\Services\DocumentIndexService;
use App\Services\EmployeeDocumentService;
use App\Services\OrganizationContractTemplateService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request, DocumentIndexService $index, OrganizationContractTemplateService $contractTemplates): View
    {
        $this->authorize('viewAny', Employee::class);

        $organization = CurrentOrganization::check();
        $role = $request->user()->roleIn($organization);
        $canManage = $role?->canManageEmployees() ?? false;
        $contractTemplates->ensureDefaults($organization);

        return view('app.documents.index', [
            'organization' => $organization,
            'documents' => $index->paginate($request),
            'employees' => Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'types' => \App\Enums\EmployeeDocumentType::cases(),
            'contractTemplates' => OrganizationContractTemplate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'canManage' => $canManage,
            'canManageOrganization' => $role?->canManageOrganization() ?? false,
        ]);
    }

    public function store(
        StoreDocumentFromIndexRequest $request,
        EmployeeDocumentService $documents,
    ): RedirectResponse {
        /** @var Employee $employee */
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $documents->store(
            $employee,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('documents.index')
            ->with('status', __('documents.uploaded'));
    }
}
