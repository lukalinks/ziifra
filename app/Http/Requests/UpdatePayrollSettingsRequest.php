<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', CurrentOrganization::check());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'remove_logo' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'payroll_settings' => ['sometimes', 'array'],
            'payroll_settings.trust_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payroll_settings.trust_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payroll_settings.vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payroll_settings.footer_note' => ['nullable', 'string', 'max:2000'],
            'payroll_settings.show_logo' => ['sometimes', 'boolean'],
            'payroll_settings.show_vat' => ['sometimes', 'boolean'],
            'payslip_template' => ['sometimes', 'array'],
            'payslip_template.layout' => ['sometimes', 'string', Rule::in(['standard', 'compact', 'detailed'])],
            'payslip_template.footer_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'payslip_template.show_logo' => ['sometimes', 'boolean'],
            'payslip_template.show_legal_block' => ['sometimes', 'boolean'],
            'payslip_template.show_employer_pension' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remove_logo' => $this->boolean('remove_logo'),
        ]);

        if ($this->has('payroll_settings')) {
            $ps = (array) $this->input('payroll_settings', []);
            $this->merge([
                'payroll_settings' => array_merge($ps, [
                    'show_logo' => $this->boolean('payroll_settings.show_logo'),
                    'show_vat' => $this->boolean('payroll_settings.show_vat'),
                ]),
            ]);
        }

        if ($this->has('payslip_template')) {
            $pt = (array) $this->input('payslip_template', []);
            $this->merge([
                'payslip_template' => [
                    'layout' => $pt['layout'] ?? 'standard',
                    'show_logo' => $this->boolean('payslip_template.show_logo'),
                    'show_legal_block' => $this->boolean('payslip_template.show_legal_block'),
                    'show_employer_pension' => $this->boolean('payslip_template.show_employer_pension'),
                    'footer_note' => blank($pt['footer_note'] ?? null) ? null : $pt['footer_note'],
                ],
            ]);
        }
    }
}
