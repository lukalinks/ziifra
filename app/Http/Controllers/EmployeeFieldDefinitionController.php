<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldType;
use App\Http\Requests\StoreEmployeeFieldDefinitionRequest;
use App\Models\EmployeeFieldDefinition;
use App\Models\Organization;
use App\Services\EmployeeCustomFieldService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeFieldDefinitionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeFieldDefinition::class);

        return view('app.settings.employee-fields', [
            'organization' => CurrentOrganization::check(),
            'definitions' => EmployeeFieldDefinition::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->withCount('values')
                ->get(),
            'types' => CustomFieldType::cases(),
        ]);
    }

    public function store(StoreEmployeeFieldDefinitionRequest $request, EmployeeCustomFieldService $service): RedirectResponse
    {
        $validated = $request->validated();

        $service->createDefinition(CurrentOrganization::check(), [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'is_required' => $validated['is_required'] ?? false,
        ]);

        return back()->with('status', 'Custom field created successfully.');
    }

    public function destroy(Organization $organization, EmployeeFieldDefinition $fieldDefinition): RedirectResponse
    {
        $this->authorize('delete', $fieldDefinition);

        $fieldDefinition->delete();

        return back()->with('status', 'Custom field removed.');
    }
}
