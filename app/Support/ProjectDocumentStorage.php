<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectDocumentStorage
{
    public const MAX_KILOBYTES = 10240;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv',
    ];

    public static function store(Project $project, UploadedFile $file): array
    {
        $directory = sprintf(
            'organizations/%d/projects/%d/documents',
            $project->organization_id,
            $project->id,
        );

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'local');

        return [
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ];
    }

    public static function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('local')->delete($path);
        }
    }

    public static function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk('local')->exists($path);
    }
}
