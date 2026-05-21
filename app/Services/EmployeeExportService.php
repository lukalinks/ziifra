<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeExportService
{
    public function download(Organization $organization): StreamedResponse
    {
        $filename = 'ziifra-employees-'.$organization->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($organization): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, EmployeeImportService::TEMPLATE_HEADERS);

            Employee::query()
                ->with(['department', 'position', 'manager'])
                ->where('organization_id', $organization->id)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->chunk(100, function ($employees) use ($handle): void {
                    foreach ($employees as $employee) {
                        fputcsv($handle, [
                            $employee->first_name,
                            $employee->last_name,
                            $employee->email ?? '',
                            $employee->phone ?? '',
                            $employee->department?->name ?? '',
                            $employee->position?->title ?? '',
                            $employee->manager?->email ?? '',
                            $employee->employment_type?->value ?? '',
                            $employee->employment_status->value,
                            $employee->start_date?->format('Y-m-d') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
