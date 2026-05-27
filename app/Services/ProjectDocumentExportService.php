<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProjectDocument;
use App\Models\WorkspaceNavItem;
use App\Models\User;
use App\Support\SpreadsheetExport;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentExportService
{
    public function taxSummaryCsv(
        Organization $organization,
        ?int $projectId,
        Carbon $start,
        Carbon $end,
    ): StreamedResponse {
        $query = ProjectDocument::query()
            ->with('project')
            ->whereBetween('uploaded_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('uploaded_at');

        $headers = [
            __('project_documents.export.project'),
            __('project_documents.export.title'),
            __('project_documents.export.category'),
            __('project_documents.export.filename'),
            __('project_documents.export.uploaded'),
        ];

        $rows = $query->get()->map(fn (ProjectDocument $doc) => [
            $doc->project->name,
            $doc->title,
            $doc->category->label(),
            $doc->original_filename,
            $doc->uploaded_at?->format('Y-m-d H:i'),
        ])->all();

        return SpreadsheetExport::csvDownload(
            'tax-summary-'.$start->format('Y-m-d').'-'.$end->format('Y-m-d').'.csv',
            $headers,
            $rows,
        );
    }
}
