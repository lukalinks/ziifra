<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class EmployeeCustomFieldFile
{
    public const MAX_KILOBYTES = 10240;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx',
    ];

    /**
     * @return array{path: string, name: string}|null
     */
    public static function decode(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded['path'])) {
            return [
                'path' => $decoded['path'],
                'name' => $decoded['name'] ?? basename($decoded['path']),
            ];
        }

        return ['path' => $value, 'name' => basename($value)];
    }

    public static function encode(string $path, string $originalName): string
    {
        return json_encode([
            'path' => $path,
            'name' => $originalName,
        ], JSON_THROW_ON_ERROR);
    }

    public static function delete(?string $value): void
    {
        $decoded = self::decode($value);

        if ($decoded === null) {
            return;
        }

        Storage::disk('local')->delete($decoded['path']);
    }

    public static function exists(?string $value): bool
    {
        $decoded = self::decode($value);

        return $decoded !== null && Storage::disk('local')->exists($decoded['path']);
    }
}
