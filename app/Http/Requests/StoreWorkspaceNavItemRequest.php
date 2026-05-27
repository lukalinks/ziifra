<?php

namespace App\Http\Requests;

use App\Models\WorkspaceNavItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceNavItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkspaceNavItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
        ];
    }
}
