<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        return view('app.settings.departments', [
            'organization' => CurrentOrganization::check(),
            'departments' => Department::query()->withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::query()->create($request->validated());

        return back()->with('status', 'Department added successfully.');
    }

    public function destroy(Organization $organization, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return back()->with('status', 'Department removed successfully.');
    }
}
