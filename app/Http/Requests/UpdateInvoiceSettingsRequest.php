<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceSettingsRequest extends FormRequest
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
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'invoice_settings' => ['required', 'array'],
            'invoice_settings.footer_text' => ['nullable', 'string', 'max:5000'],
            'invoice_settings.vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_settings.vat_manual' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('invoice_settings')) {
            $this->merge([
                'invoice_settings' => array_merge((array) $this->input('invoice_settings'), [
                    'vat_manual' => $this->boolean('invoice_settings.vat_manual'),
                ]),
            ]);
        }
    }
}
