<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Support\ProjectDocumentStorage;
use Illuminate\Http\UploadedFile;

class ProjectDocumentService
{
    public function store(
        Project $project,
        UploadedFile $file,
        array $data,
        User $uploadedBy,
    ): ProjectDocument {
        $stored = ProjectDocumentStorage::store($project, $file);

        return ProjectDocument::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'uploaded_by_user_id' => $uploadedBy->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'amount' => isset($data['amount']) && $data['amount'] !== '' && $data['amount'] !== null
                ? round((float) $data['amount'], 2)
                : null,
            'file_path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'uploaded_at' => now(),
        ]);
    }

    public function delete(ProjectDocument $document): void
    {
        $document->delete();
    }
}
