<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
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
        $enabled = $this->boolean('mail_settings.enabled');

        return [
            'mail_settings' => ['required', 'array'],
            'mail_settings.enabled' => ['sometimes', 'boolean'],
            'mail_settings.host' => [Rule::requiredIf($enabled), 'nullable', 'string', 'max:255'],
            'mail_settings.port' => [Rule::requiredIf($enabled), 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_settings.encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl', 'none', ''])],
            'mail_settings.username' => ['nullable', 'string', 'max:255'],
            'mail_settings.password' => [
                Rule::requiredIf($enabled && ! $organization->resolvedMailSettings()['password']),
                'nullable',
                'string',
                'max:500',
            ],
            'mail_settings.from_address' => [Rule::requiredIf($enabled), 'nullable', 'email', 'max:255'],
            'mail_settings.from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mail_settings' => array_merge($this->input('mail_settings', []), [
                'enabled' => $this->boolean('mail_settings.enabled'),
            ]),
        ]);
    }
}
