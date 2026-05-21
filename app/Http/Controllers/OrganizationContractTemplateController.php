<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationContractTemplateRequest;
use App\Http\Requests\UpdateOrganizationContractTemplateRequest;
use App\Models\Organization;
use App\Models\OrganizationContractTemplate;
use App\Services\OrganizationContractTemplateService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationContractTemplateController extends Controller
{
    public function index(OrganizationContractTemplateService $templates): View
    {
        $this->authorize('viewAny', OrganizationContractTemplate::class);

        $organization = CurrentOrganization::check();
        $templates->ensureDefaults($organization);

        return view('app.settings.contract-templates.index', [
            'organization' => $organization,
            'contractTemplates' => OrganizationContractTemplate::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'placeholders' => OrganizationContractTemplateService::availablePlaceholders(),
        ]);
    }

    public function store(
        StoreOrganizationContractTemplateRequest $request,
        OrganizationContractTemplateService $templates,
    ): RedirectResponse {
        $organization = CurrentOrganization::check();
        $sortOrder = OrganizationContractTemplate::query()->max('sort_order');

        OrganizationContractTemplate::query()->create([
            ...$request->validated(),
            'organization_id' => $organization->id,
            'slug' => $templates->uniqueSlug($organization, $request->string('name')->toString()),
            'sort_order' => ($sortOrder ?? 0) + 1,
            'is_active' => true,
            'is_system' => false,
        ]);

        return back()->with('status', __('documents.templates.settings.created'));
    }

    public function edit(Organization $organization, OrganizationContractTemplate $template): View
    {
        $this->authorize('update', $template);

        return view('app.settings.contract-templates.edit', [
            'organization' => $organization,
            'template' => $template,
            'placeholders' => OrganizationContractTemplateService::availablePlaceholders(),
        ]);
    }

    public function update(
        UpdateOrganizationContractTemplateRequest $request,
        Organization $organization,
        OrganizationContractTemplate $template,
        OrganizationContractTemplateService $templates,
    ): RedirectResponse {
        $name = $request->string('name')->toString();

        $template->update([
            'name' => $name,
            'description' => $request->input('description'),
            'body' => $request->input('body'),
            'is_active' => $request->boolean('is_active', true),
            'slug' => $templates->uniqueSlug($organization, $name, $template->id),
        ]);

        return redirect()
            ->route('settings.contract-templates.index')
            ->with('status', __('documents.templates.settings.updated'));
    }

    public function destroy(Organization $organization, OrganizationContractTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        if ($template->is_system) {
            return back()->withErrors([
                'template' => __('documents.templates.settings.cannot_delete_system'),
            ]);
        }

        $template->delete();

        return back()->with('status', __('documents.templates.settings.deleted'));
    }
}
