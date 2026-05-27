<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFromIndexRequest;
use App\Models\DocumentFolder;
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

        $contractTemplates = OrganizationContractTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedContractSlug = $request->query('contract');
        if (! $contractTemplates->contains('slug', $selectedContractSlug)) {
            $selectedContractSlug = $contractTemplates->first()?->slug;
        }

        $typeCounts = $index->countsByType();
        $customFolders = DocumentFolder::query()
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        $selectedType = $request->string('type')->toString();
        if ($selectedType !== '' && ! in_array($selectedType, array_column(\App\Enums\EmployeeDocumentType::cases(), 'value'), true)) {
            $selectedType = '';
        }

        $selectedFolder = null;
        if ($folderId = $request->integer('folder')) {
            $selectedFolder = DocumentFolder::query()->findOrFail($folderId);
            $selectedType = '';
        }

        $hasFilters = $request->hasAny(['search', 'employee_id', 'expiry']);
        $summaryStats = $index->summaryStats();

        return view('app.documents.index', [
            'organization' => $organization,
            'documents' => $index->paginate($request),
            'employees' => Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'types' => \App\Enums\EmployeeDocumentType::cases(),
            'typeCounts' => $typeCounts,
            'customFolders' => $customFolders,
            'totalDocumentCount' => $summaryStats['total'],
            'summaryStats' => $summaryStats,
            'selectedType' => $selectedType !== '' ? $selectedType : null,
            'selectedFolder' => $selectedFolder,
            'hasFilters' => $hasFilters,
            'showFolderContents' => $selectedType !== '' || $selectedFolder !== null || $hasFilters,
            'contractTemplates' => $contractTemplates,
            'selectedContractSlug' => $selectedContractSlug,
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

        $validated = $request->validated();

        return redirect()
            ->route('documents.index', array_filter([
                'folder' => $validated['document_folder_id'] ?? null,
                'type' => empty($validated['document_folder_id']) ? ($validated['type'] ?? null) : null,
            ]))
            ->with('status', __('documents.uploaded'));
    }
}
