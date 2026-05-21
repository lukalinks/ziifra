<?php

namespace App\Http\Requests\Concerns;

use App\Enums\CustomFieldType;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Support\CurrentOrganization;
use App\Support\EmployeeCustomFieldFile;
use Illuminate\Validation\Rule;

trait ValidatesEmployeeCustomFields
{
    /**
     * @return array<string, mixed>
     */
    protected function customFieldRules(): array
    {
        $organizationId = CurrentOrganization::check()->id;
        $fileRules = ['nullable', 'file', 'max:'.EmployeeCustomFieldFile::MAX_KILOBYTES, 'mimes:'.implode(',', EmployeeCustomFieldFile::ALLOWED_MIMES)];

        $rules = [
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.definition_id' => [
                'required',
                Rule::exists('employee_field_definitions', 'id')
                    ->where('organization_id', $organizationId),
            ],
            'custom_fields.*.value' => ['nullable'],
            'custom_fields.*.file' => $fileRules,
            'custom_fields.*.remove_file' => ['nullable', 'boolean'],
        ];

        $canDefine = auth()->user()?->can('create', EmployeeFieldDefinition::class) ?? false;

        if ($canDefine) {
            return array_merge($rules, [
                'new_custom_fields' => ['nullable', 'array'],
                'new_custom_fields.*.name' => ['required', 'string', 'max:255'],
                'new_custom_fields.*.type' => ['required', Rule::enum(CustomFieldType::class)],
                'new_custom_fields.*.options' => ['nullable', 'string', 'max:1000'],
                'new_custom_fields.*.is_required' => ['nullable', 'boolean'],
                'new_custom_fields.*.value' => ['nullable'],
                'new_custom_fields.*.file' => $fileRules,
            ]);
        }

        return array_merge($rules, [
            'new_custom_fields' => ['prohibited'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $organizationId = CurrentOrganization::check()->id;
            $employee = $this->route('employee');

            foreach ($this->input('custom_fields', []) as $index => $row) {
                $definitionId = $row['definition_id'] ?? null;
                if ($definitionId === null) {
                    continue;
                }

                $definition = EmployeeFieldDefinition::query()
                    ->where('organization_id', $organizationId)
                    ->find($definitionId);

                if ($definition === null) {
                    continue;
                }

                if ($definition->type === CustomFieldType::File) {
                    $this->validateFileField(
                        $validator,
                        $definition,
                        $this->file("custom_fields.{$index}.file"),
                        $this->boolean("custom_fields.{$index}.remove_file"),
                        $employee,
                        $definitionId,
                        "custom_fields.{$index}.file",
                    );
                } else {
                    $this->validateFieldValue($validator, "custom_fields.{$index}.value", $definition, $row['value'] ?? null);
                }
            }

            if (auth()->user()?->can('create', EmployeeFieldDefinition::class)) {
                foreach ($this->input('new_custom_fields', []) as $index => $row) {
                    if (empty($row['name'])) {
                        continue;
                    }

                    $type = $row['type'] ?? CustomFieldType::Text->value;

                    if ($type === CustomFieldType::Select->value && empty(trim((string) ($row['options'] ?? '')))) {
                        $validator->errors()->add("new_custom_fields.{$index}.options", 'Provide comma-separated options for dropdown fields.');
                    }

                    if ($type === CustomFieldType::File->value) {
                        $tempDefinition = new EmployeeFieldDefinition([
                            'name' => $row['name'],
                            'type' => CustomFieldType::File,
                            'is_required' => (bool) ($row['is_required'] ?? false),
                        ]);

                        $this->validateFileField(
                            $validator,
                            $tempDefinition,
                            $this->file("new_custom_fields.{$index}.file"),
                            false,
                            null,
                            null,
                            "new_custom_fields.{$index}.file",
                        );
                    } elseif ((bool) ($row['is_required'] ?? false) && empty($row['value'])) {
                        $validator->errors()->add("new_custom_fields.{$index}.value", 'This field is required.');
                    }
                }
            }
        });
    }

    protected function validateFileField(
        $validator,
        EmployeeFieldDefinition $definition,
        mixed $upload,
        bool $remove,
        ?Employee $employee,
        ?int $definitionId,
        string $fileKey,
    ): void {
        $hasExisting = false;

        if ($employee && $definitionId) {
            $hasExisting = $employee->fieldValues()
                ->where('employee_field_definition_id', $definitionId)
                ->whereNotNull('value')
                ->exists();
        }

        if ($definition->is_required && ! $upload && ! $hasExisting && ! $remove) {
            $validator->errors()->add($fileKey, "{$definition->name} is required.");
        }
    }

    protected function validateFieldValue($validator, string $key, EmployeeFieldDefinition $definition, mixed $value): void
    {
        if ($definition->is_required && ($value === null || $value === '')) {
            $validator->errors()->add($key, "{$definition->name} is required.");

            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if ($definition->type === CustomFieldType::Select && $definition->options) {
            if (! in_array((string) $value, $definition->options, true)) {
                $validator->errors()->add($key, 'Please select a valid option.');
            }
        }
    }
}
