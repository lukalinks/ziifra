<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEmployeeCustomFields;
use App\Http\Requests\Concerns\ValidatesEmployeeFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    use ValidatesEmployeeCustomFields;
    use ValidatesEmployeeFields;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            $this->employeeFieldRules($this->route('employee')),
            $this->customFieldRules(),
        );
    }
}
