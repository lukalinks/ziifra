<?php

namespace App\Http\Requests;

use App\Support\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Position::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = CurrentOrganization::check()->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'title')->where('organization_id', $organizationId),
            ],
        ];
    }
}
