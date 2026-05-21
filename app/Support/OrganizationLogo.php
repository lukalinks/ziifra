<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganizationLogo
{
    public const MAX_KILOBYTES = 2048;

    /** @var list<string> */
    public const ALLOWED_MIMES = ['jpg', 'jpeg', 'png', 'webp'];

    public static function store(Organization $organization, UploadedFile $file): string
    {
        self::delete($organization->logo_path);

        $extension = $file->guessExtension() ?: 'png';
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = "organizations/{$organization->id}/branding";

        return $file->storeAs($directory, $filename, 'local');
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('local')->delete($path);
    }

    public static function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk('local')->exists($path);
    }
}
