<?php

namespace App\Http\Requests;

use App\Models\ExpenseClaim;
use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        $claim = $this->route('expenseClaim');

        return $claim instanceof ExpenseClaim && $this->user()->can('approve', $claim);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
