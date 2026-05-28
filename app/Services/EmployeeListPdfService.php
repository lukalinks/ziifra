<?php

namespace App\Services;

use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeListPdfService
{
    public function download(Organization $organization)
    {
        $employees = $organization->employees()
            ->with(['department', 'position'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Pdf::loadView('app.employees.export-pdf', [
            'organization' => $organization,
            'employees' => $employees,
        ])->download('employees-'.$organization->slug.'.pdf');
    }
}
