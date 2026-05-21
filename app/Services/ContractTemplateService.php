<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationContractTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ContractTemplateService
{
    /**
     * @return array<string, string>
     */
    public function placeholders(
        Organization $organization,
        ?Employee $employee,
        OrganizationContractTemplate $template,
    ): array {
        $blank = $employee === null;

        return [
            'company_name' => $blank ? __('documents.templates.placeholders.company_name') : ($organization->legal_name ?: $organization->name),
            'company_address' => $blank ? __('documents.templates.placeholders.company_address') : ($organization->formattedAddress() ?: '—'),
            'company_registration' => $blank ? __('documents.templates.placeholders.company_registration') : ($organization->registration_number ?: '—'),
            'company_fiscal' => $blank ? __('documents.templates.placeholders.company_fiscal') : ($organization->fiscal_number ?: '—'),
            'signatory_name' => $blank ? __('documents.templates.placeholders.signatory_name') : ($organization->signatory_name ?: '—'),
            'signatory_title' => $blank ? __('documents.templates.placeholders.signatory_title') : ($organization->signatory_title ?: '—'),
            'employee_name' => $blank ? __('documents.templates.placeholders.employee_name') : $employee->fullName(),
            'employee_email' => $blank ? __('documents.templates.placeholders.employee_email') : ($employee->email ?: '—'),
            'employee_phone' => $blank ? __('documents.templates.placeholders.employee_phone') : ($employee->phone ?: '—'),
            'employee_position' => $blank ? __('documents.templates.placeholders.employee_position') : ($employee->position?->name ?: '—'),
            'employee_department' => $blank ? __('documents.templates.placeholders.employee_department') : ($employee->department?->name ?: '—'),
            'employment_type' => $blank ? __('documents.templates.placeholders.employment_type') : $employee->employment_type->label(),
            'start_date' => $blank ? __('documents.templates.placeholders.start_date') : ($employee->start_date?->format('d/m/Y') ?: '—'),
            'gross_salary' => $blank
                ? __('documents.templates.placeholders.gross_salary')
                : ($employee->gross_salary !== null
                    ? number_format((float) $employee->gross_salary, 2).' '.($organization->currency ?? 'EUR')
                    : '—'),
            'contract_date' => now()->timezone($organization->timezone ?? config('app.timezone'))->format('d/m/Y'),
            'probation_days' => $blank
                ? __('documents.templates.placeholders.probation_days')
                : (string) ($organization->probation_days ?? 90),
            'end_date' => $blank ? __('documents.templates.placeholders.end_date') : '—',
            'template_title' => $template->name,
        ];
    }

    public function renderBody(string $body, array $fields): string
    {
        $replacements = [];

        foreach ($fields as $key => $value) {
            $replacements[':'.$key] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }

    public function makePdf(
        Organization $organization,
        OrganizationContractTemplate $template,
        ?Employee $employee = null,
    ): \Barryvdh\DomPDF\PDF {
        if ($employee !== null) {
            $employee->loadMissing(['department', 'position']);
        }

        $fields = $this->placeholders($organization, $employee, $template);
        $logoDataUri = $organization->payslipLogoDataUri();

        return Pdf::loadView('app.documents.templates.pdf', [
            'organization' => $organization,
            'template' => $template,
            'employee' => $employee,
            'fields' => $fields,
            'bodyHtml' => $this->renderBody($template->body, $fields),
            'logoDataUri' => $logoDataUri,
            'primaryColor' => $organization->primary_color ?? '#1e3a5f',
            'isBlank' => $employee === null,
        ])->setPaper('a4', 'portrait');
    }

    public function suggestedFilename(OrganizationContractTemplate $template, ?Employee $employee = null): string
    {
        $slug = Str::slug($template->slug);

        if ($employee === null) {
            return 'contract-template-'.$slug.'.pdf';
        }

        return 'contract-'.$slug.'-'.Str::slug($employee->fullName()).'.pdf';
    }
}
