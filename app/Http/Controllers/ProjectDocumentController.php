<?php

namespace App\Http\Controllers;

use App\Enums\ProjectDocumentCategory;
use App\Http\Requests\StoreProjectDocumentRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\ProjectDocumentExportService;
use App\Services\ProjectDocumentService;
use App\Support\CurrentOrganization;
use App\Support\ProjectDocumentStorage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectDocument::class);

        $organization = CurrentOrganization::check();
        $selectedProject = null;

        if ($projectId = $request->integer('project')) {
            $selectedProject = Project::query()->findOrFail($projectId);
        }

        $viewAll = $request->string('view')->toString() === 'all';
        $search = $request->string('search')->trim()->toString();
        $selectedCategory = $request->string('category')->toString();
        $categoryFilter = ProjectDocumentCategory::tryFrom($selectedCategory);

        $showProjectContents = $selectedProject !== null
            || $viewAll
            || $search !== ''
            || $categoryFilter !== null;

        $documentsQuery = ProjectDocument::query()
            ->with(['project', 'uploadedBy'])
            ->when($selectedProject, fn ($q) => $q->where('project_id', $selectedProject->id))
            ->when($categoryFilter, fn ($q) => $q->where('category', $categoryFilter))
            ->when($search, function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('original_filename', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('uploaded_at');

        $documents = $showProjectContents
            ? $documentsQuery->paginate(20)->withQueryString()
            : collect();

        $projects = Project::query()->withCount('documents')->orderBy('name')->get();

        $categoryCounts = ProjectDocument::query()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->category instanceof ProjectDocumentCategory
                    ? $row->category->value
                    : (string) $row->category => (int) $row->aggregate,
            ]);

        $summaryStats = [
            'total' => ProjectDocument::query()->count(),
            'projects' => Project::query()->whereHas('documents')->count(),
            'this_month' => ProjectDocument::query()
                ->where('uploaded_at', '>=', now()->startOfMonth())
                ->count(),
            'categories' => $categoryCounts->filter()->count(),
        ];

        $recentDocuments = ! $showProjectContents
            ? ProjectDocument::query()
                ->with(['project', 'uploadedBy'])
                ->orderByDesc('uploaded_at')
                ->limit(5)
                ->get()
            : collect();

        $hasFilters = $search !== '' || $categoryFilter !== null;

        return view('app.project-documents.index', [
            'organization' => $organization,
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'selectedCategory' => $categoryFilter,
            'viewAll' => $viewAll,
            'showProjectContents' => $showProjectContents,
            'hasFilters' => $hasFilters,
            'documents' => $documents,
            'recentDocuments' => $recentDocuments,
            'summaryStats' => $summaryStats,
            'categoryCounts' => $categoryCounts,
            'categories' => ProjectDocumentCategory::cases(),
            'canManage' => $request->user()->can('create', ProjectDocument::class),
            'exportDefaults' => [
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
            ],
        ]);
    }

    public function store(
        StoreProjectDocumentRequest $request,
        Organization $organization,
        ProjectDocumentService $documents,
    ): RedirectResponse {
        $routeProject = $request->route('project');
        /** @var Project $project */
        $project = $routeProject instanceof Project
            ? $routeProject
            : Project::query()
                ->where('organization_id', $organization->id)
                ->findOrFail($request->validated('project_id'));

        $documents->store(
            $project,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        $redirect = $request->boolean('from_project')
            ? redirect()->route('projects.show', ['project' => $project, 'tab' => 'documents'])
            : redirect()->route('project-documents.index', ['project' => $project->id]);

        return $redirect->with('status', __('project_documents.uploaded'));
    }

    public function download(
        Organization $organization,
        ProjectDocument $projectDocument,
    ): StreamedResponse {
        abort_unless($projectDocument->organization_id === $organization->id, 404);
        abort_unless(ProjectDocumentStorage::exists($projectDocument->file_path), 404);

        return Storage::disk('local')->download(
            $projectDocument->file_path,
            $projectDocument->original_filename,
        );
    }

    public function destroy(
        Organization $organization,
        ProjectDocument $projectDocument,
        Request $request,
        ProjectDocumentService $documents,
    ): RedirectResponse {
        $this->authorize('delete', $projectDocument);

        $project = $projectDocument->project;
        $documents->delete($projectDocument);

        if ($request->boolean('from_project') && $project) {
            return redirect()
                ->route('projects.show', ['project' => $project, 'tab' => 'documents'])
                ->with('status', __('project_documents.deleted'));
        }

        return redirect()
            ->route('project-documents.index', ['project' => $project?->id])
            ->with('status', __('project_documents.deleted'));
    }

    public function exportSummary(Request $request, ProjectDocumentExportService $export): StreamedResponse
    {
        $this->authorize('viewAny', ProjectDocument::class);

        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        return $export->taxSummaryCsv(
            CurrentOrganization::check(),
            $validated['project_id'] ?? null,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
        );
    }
}
