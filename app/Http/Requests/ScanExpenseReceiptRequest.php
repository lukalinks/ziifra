<?php

namespace App\Http\Requests;

use App\Support\ExpenseReceiptStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ScanExpenseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ExpenseClaim::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'receipt' => [
                'required',
                File::types(array_values(array_filter(
                    ExpenseReceiptStorage::ALLOWED_MIMES,
                    fn (string $mime) => $mime !== 'pdf',
                )))->max(ExpenseReceiptStorage::MAX_KILOBYTES),
            ],
        ];
    }
}
