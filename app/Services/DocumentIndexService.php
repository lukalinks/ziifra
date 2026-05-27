<?php

namespace App\Services;

use App\Enums\EmployeeDocumentType;
use App\Models\DocumentFolder;
use App\Models\EmployeeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DocumentIndexService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = EmployeeDocument::query()
            ->with(['employee.department', 'employee.position', 'uploadedBy'])
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhereHas('employee', function (Builder $employeeQuery) use ($search): void {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($folderId = $request->integer('folder')) {
            $query->where('document_folder_id', $folderId);
        } elseif ($type = $request->string('type')->toString()) {
            if (in_array($type, array_column(EmployeeDocumentType::cases(), 'value'), true)) {
                $query->where('type', $type)
                    ->whereNull('document_folder_id');
            }
        }

        match ($request->string('expiry')->toString()) {
            'expiring' => $query->whereNotNull('expires_at')
                ->whereDate('expires_at', '>', now())
                ->whereDate('expires_at', '<=', now()->addDays(30)),
            'expired' => $query->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', now()),
            'none' => $query->whereNull('expires_at'),
            default => null,
        };

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function countsByType(): array
    {
        $counts = EmployeeDocument::query()
            ->whereNull('document_folder_id')
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $result = [];

        foreach (EmployeeDocumentType::cases() as $type) {
            $result[$type->value] = (int) ($counts[$type->value] ?? 0);
        }

        return $result;
    }

    /**
     * @return array{total: int, expiring: int, expired: int}
     */
    public function summaryStats(): array
    {
        return [
            'total' => EmployeeDocument::query()->count(),
            'expiring' => EmployeeDocument::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>', now())
                ->whereDate('expires_at', '<=', now()->addDays(30))
                ->count(),
            'expired' => EmployeeDocument::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', now())
                ->count(),
        ];
    }
}
