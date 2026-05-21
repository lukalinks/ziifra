<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice && $this->user()->can('update', $invoice);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreInvoiceRequest)->rules();
    }
}
