<?php

namespace App\Http\Requests;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\PayrollAllowanceKind;
use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $run = $this->route('payrollRun');

        return $run instanceof PayrollRun && $this->user()->can('update', $run);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*' => ['array'],
            'items.*.base_gross_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.gross_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.allowances' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.allowance_lines' => ['nullable', 'array'],
            'items.*.allowance_lines.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.allowance_lines.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.allowance_lines.*.tax_treatment' => ['nullable', Rule::enum(AllowanceTaxTreatment::class)],
            'items.*.allowance_lines.*.kind' => ['nullable', Rule::enum(PayrollAllowanceKind::class)],
            'items.*.allowance_lines.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
