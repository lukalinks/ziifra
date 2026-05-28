<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChatSettingsRequest extends FormRequest
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
            'chat_settings' => ['required', 'array'],
            'chat_settings.enabled' => ['sometimes', 'boolean'],
            'chat_settings.employees_can_write' => ['sometimes', 'boolean'],
            'chat_settings.private_chat_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chat_settings' => [
                'enabled' => $this->boolean('chat_settings.enabled'),
                'employees_can_write' => $this->boolean('chat_settings.employees_can_write'),
                'private_chat_enabled' => $this->boolean('chat_settings.private_chat_enabled'),
            ],
        ]);
    }
}
