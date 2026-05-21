<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\OrganizationLegalForm;
use App\Enums\WorkWeekDay;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use App\Support\OrganizationLogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateOrganizationSettingsRequest extends FormRequest
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
        $organization = CurrentOrganization::check();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'legal_form' => ['nullable', Rule::enum(OrganizationLegalForm::class)],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'fiscal_number' => ['nullable', 'string', 'max:64'],
            'vat_number' => ['nullable', 'string', 'max:64'],
            'vat_registered' => ['sometimes', 'boolean'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'hr_email' => ['nullable', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', Rule::in(Organization::supportedTimezones())],
            'currency' => ['required', 'string', Rule::in(Organization::supportedCurrencies())],
            'locale' => ['required', 'string', Rule::in(Organization::supportedLocales())],
            'work_week_days' => ['required', 'array', 'min:1'],
            'work_week_days.*' => [Rule::enum(WorkWeekDay::class)],
            'fiscal_year_start_month' => ['required', 'integer', Rule::in(Organization::supportedFiscalYearMonths())],
            'date_format' => ['required', 'string', Rule::in(array_keys(Organization::supportedDateFormats()))],
            'observe_kosovo_holidays' => ['sometimes', 'boolean'],
            'default_employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'probation_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'employee_id_prefix' => ['nullable', 'string', 'max:16', 'regex:/^[A-Za-z0-9_-]+$/'],
            'handbook_url' => ['nullable', 'url', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'hr_can_invite' => ['sometimes', 'boolean'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'accent_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'brand_tagline' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'max:'.OrganizationLogo::MAX_KILOBYTES, 'mimes:'.implode(',', OrganizationLogo::ALLOWED_MIMES)],
            'remove_logo' => ['sometimes', 'boolean'],
            'payslip_template' => ['sometimes', 'array'],
            'payslip_template.layout' => ['sometimes', 'string', Rule::in(['standard', 'compact', 'detailed'])],
            'payslip_template.footer_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'payslip_template.show_logo' => ['sometimes'],
            'payslip_template.show_legal_block' => ['sometimes'],
            'payslip_template.show_employer_pension' => ['sometimes'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $legalForm = $this->input('legal_form');
        $slug = Str::slug((string) $this->input('slug', ''));

        $this->merge([
            'remove_logo' => $this->boolean('remove_logo'),
            'observe_kosovo_holidays' => $this->boolean('observe_kosovo_holidays'),
            'vat_registered' => $this->boolean('vat_registered'),
            'hr_can_invite' => $this->boolean('hr_can_invite'),
            'country_code' => strtoupper((string) $this->input('country_code', 'XK')),
            'legal_form' => $legalForm === '' || $legalForm === null ? null : $legalForm,
            'slug' => $slug,
            'website' => $this->normalizeWebsite($this->input('website')),
            'handbook_url' => $this->normalizeWebsite($this->input('handbook_url')),
            'primary_color' => $this->blankToNull($this->input('primary_color')),
            'accent_color' => $this->blankToNull($this->input('accent_color')),
            'employee_id_prefix' => $this->blankToNull($this->input('employee_id_prefix')),
            'bank_name' => $this->blankToNull($this->input('bank_name')),
            'bank_iban' => $this->blankToNull($this->input('bank_iban')),
            'signatory_name' => $this->blankToNull($this->input('signatory_name')),
            'signatory_title' => $this->blankToNull($this->input('signatory_title')),
            'work_week_days' => array_values(array_unique((array) $this->input('work_week_days', []))),
        ]);

        if ($this->has('payslip_template')) {
            $pt = (array) $this->input('payslip_template', []);
            $layout = $pt['layout'] ?? 'standard';
            if (! in_array($layout, ['standard', 'compact', 'detailed'], true)) {
                $layout = 'standard';
            }

            $this->merge([
                'payslip_template' => [
                    'layout' => $layout,
                    'show_logo' => $this->boolean('payslip_template.show_logo'),
                    'show_legal_block' => $this->boolean('payslip_template.show_legal_block'),
                    'show_employer_pension' => $this->boolean('payslip_template.show_employer_pension'),
                    'footer_note' => $this->blankToNull($pt['footer_note'] ?? null),
                ],
            ]);
        }
    }

    protected function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeWebsite(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        return $value;
    }
}
