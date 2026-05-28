<?php

namespace App\Http\Requests;

use App\Models\ChatMessage;
use App\Services\ChatService;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ChatMessage::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'recipient_user_id' => [
                'nullable',
                'integer',
                Rule::notIn([$this->user()->id]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $recipientId = $this->input('recipient_user_id');

        if ($recipientId === '' || $recipientId === null) {
            $this->merge(['recipient_user_id' => null]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $recipientId = $this->input('recipient_user_id');

            if ($recipientId === null) {
                return;
            }

            $organization = CurrentOrganization::check();

            if (! app(ChatService::class)->userBelongsToOrganization($organization, (int) $recipientId)) {
                $validator->errors()->add('recipient_user_id', __('chat.invalid_recipient'));
            }
        });
    }
}
