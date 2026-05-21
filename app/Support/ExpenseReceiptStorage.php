<?php

namespace App\Support;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseReceiptStorage
{
    public const MAX_KILOBYTES = 10240;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'pdf', 'jpg', 'jpeg', 'png', 'webp',
    ];

    public static function store(Employee $employee, UploadedFile $file): array
    {
        $directory = sprintf(
            'organizations/%d/employees/%d/expenses',
            $employee->organization_id,
            $employee->id,
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
