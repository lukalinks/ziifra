<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeExportService;
use App\Services\EmployeeImportService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmployeeImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('app.employees.import', [
            'organization' => CurrentOrganization::check(),
        ]);
    }

    public function template(EmployeeImportService $import): Response
    {
        $this->authorize('create', Employee::class);

        return response($import->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ziifra-employee-import-template.csv"',
        ]);
    }

    public function export(EmployeeExportService $export): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        return $export->download(CurrentOrganization::check());
    }

    public function store(Request $request, EmployeeImportService $import): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $organization = CurrentOrganization::check();

        try {
            $result = $import->import($organization, $validated['file']);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('employees.import')
            ->with('import_result', $result);
    }
}
