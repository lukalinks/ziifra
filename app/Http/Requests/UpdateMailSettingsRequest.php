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
        $hasStoredPassword = filled($organization->resolvedMailSettings()['password']);

        return [
            'mail_settings' => ['required', 'array'],
            'mail_settings.enabled' => ['sometimes', 'boolean'],
            'mail_settings.host' => [
                Rule::requiredIf($enabled),
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9.\-]+$/',
            ],
            'mail_settings.port' => [Rule::requiredIf($enabled), 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_settings.encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl', 'none', ''])],
            'mail_settings.username' => ['nullable', 'string', 'max:255'],
            'mail_settings.password' => [
                Rule::requiredIf($enabled && ! $hasStoredPassword && filled($this->input('mail_settings.username'))),
                'nullable',
                'string',
                'max:500',
            ],
            'mail_settings.from_address' => [Rule::requiredIf($enabled), 'nullable', 'email', 'max:255'],
            'mail_settings.from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'mail_settings.host' => __('settings.mail.host'),
            'mail_settings.port' => __('settings.mail.port'),
            'mail_settings.username' => __('settings.mail.username'),
            'mail_settings.password' => __('settings.mail.password'),
            'mail_settings.from_address' => __('settings.mail.from_address'),
            'mail_settings.from_name' => __('settings.mail.from_name'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_settings.host.regex' => __('settings.mail.host_invalid'),
            'mail_settings.password.required' => __('settings.mail.password_required'),
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
