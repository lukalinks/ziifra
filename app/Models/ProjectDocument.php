<?php

namespace App\Models;

use App\Enums\ProjectDocumentCategory;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\ProjectDocumentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'project_id',
        'uploaded_by_user_id',
        'category',
        'title',
        'file_path',
        'original_filename',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProjectDocumentCategory::class,
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectDocument $document): void {
            ProjectDocumentStorage::delete($document->file_path);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
