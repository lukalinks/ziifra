<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeDocumentType;
use App\Http\Requests\GenerateContractTemplateRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationContractTemplate;
use App\Services\ContractTemplateService;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ContractTemplateController extends Controller
{
    public function download(
        Organization $organization,
        OrganizationContractTemplate $template,
        ContractTemplateService $templates,
    ): Response {
        $this->authorize('viewAny', Employee::class);

        abort_unless($template->is_active, 404);

        return $templates->makePdf($organization, $template)
            ->download($templates->suggestedFilename($template));
    }

    public function generate(
        GenerateContractTemplateRequest $request,
        Organization $organization,
        OrganizationContractTemplate $template,
        ContractTemplateService $templates,
        EmployeeDocumentService $documents,
    ): RedirectResponse|Response {
        abort_unless($template->is_active, 404);

        /** @var Employee $employee */
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $pdf = $templates->makePdf($organization, $template, $employee);
        $filename = $templates->suggestedFilename($template, $employee);

        if ($request->boolean('save_to_documents')) {
            $documents->storeFromBinary(
                $employee,
                $pdf->output(),
                $filename,
                [
                    'type' => EmployeeDocumentType::Contract->value,
                    'title' => $template->documentTitle($employee->fullName()),
                    'notes' => __('documents.templates.generated_note', ['template' => $template->name]),
                ],
                $request->user(),
            );

            return redirect()
                ->route('documents.index')
                ->with('status', __('documents.templates.saved', ['employee' => $employee->fullName()]));
        }

        return $pdf->download($filename);
    }
}
