<?php

namespace App\Services;

use App\Enums\CustomFieldType;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use App\Support\EmployeeCustomFieldFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EmployeeCustomFieldService
{
    /**
     * @param  array<int, array{definition_id?: int, value?: mixed, remove_file?: bool}>  $existingFields
     * @param  array<int, array{name?: string, type?: string, options?: string, is_required?: bool, value?: mixed}>  $newFields
     */
    public function syncForEmployee(Employee $employee, array $existingFields, array $newFields, Request $request): void
    {
        $organization = CurrentOrganization::check();
        $canDefineFields = Auth::user()?->can('create', EmployeeFieldDefinition::class) ?? false;

        foreach ($newFields as $index => $field) {
            if (! $canDefineFields) {
                continue;
            }

            if (empty($field['name']) || empty($field['type'])) {
                continue;
            }

            $definition = $this->createDefinition($organization, $field);
            $existingFields[] = [
                'definition_id' => $definition->id,
                'value' => $field['value'] ?? null,
                '_new_index' => $index,
            ];
        }

        $definitions = EmployeeFieldDefinition::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('id');

        $existingValues = $employee->fieldValues()->get()->keyBy('employee_field_definition_id');

        foreach ($existingFields as $row) {
            $definitionId = $row['definition_id'] ?? null;
            if ($definitionId === null || ! $definitions->has($definitionId)) {
                continue;
            }

            $definition = $definitions->get($definitionId);
            $currentValue = $existingValues->get($definition->id)?->value;

            if ($definition->type === CustomFieldType::File) {
                $indexKey = $row['_new_index'] ?? $definition->id;
                $upload = isset($row['_new_index'])
                    ? $request->file("new_custom_fields.{$indexKey}.file")
                    : $request->file("custom_fields.{$definition->id}.file");
                $remove = (bool) ($row['remove_file'] ?? $request->boolean("custom_fields.{$definition->id}.remove_file"));

                $normalized = $this->resolveFileValue($employee, $definition, $upload, $currentValue, $remove);
            } else {
                $normalized = $this->normalizeValue($definition, $row['value'] ?? null);
            }

            if ($normalized === null && ! $definition->is_required) {
                if ($definition->type === CustomFieldType::File && $currentValue) {
                    EmployeeCustomFieldFile::delete($currentValue);
                }
                $employee->fieldValues()
                    ->where('employee_field_definition_id', $definition->id)
                    ->delete();

                continue;
            }

            $employee->fieldValues()->updateOrCreate(
                ['employee_field_definition_id' => $definition->id],
                ['value' => $normalized],
            );
        }
    }

    /**
     * @param  array{name: string, type: string, options?: string, is_required?: bool}  $field
     */
    public function createDefinition(Organization $organization, array $field): EmployeeFieldDefinition
    {
        $type = CustomFieldType::from($field['type']);
        $options = null;

        if ($type === CustomFieldType::Select && ! empty($field['options'])) {
            $options = array_values(array_filter(array_map(
                trim(...),
                explode(',', (string) $field['options']),
            )));
        }

        $sortOrder = EmployeeFieldDefinition::query()
            ->where('organization_id', $organization->id)
            ->max('sort_order');

        return EmployeeFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'name' => $field['name'],
            'key' => $this->uniqueKey($organization, $field['name']),
            'type' => $type,
            'options' => $options,
            'is_required' => (bool) ($field['is_required'] ?? false),
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
    }

    public function storeUploadedFile(Employee $employee, EmployeeFieldDefinition $definition, UploadedFile $file): string
    {
        $directory = sprintf(
            'organizations/%d/employees/%d/custom-fields/%d',
            $employee->organization_id,
            $employee->id,
            $definition->id,
        );

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'local');

        return EmployeeCustomFieldFile::encode($path, $file->getClientOriginalName());
    }

    protected function resolveFileValue(
        Employee $employee,
        EmployeeFieldDefinition $definition,
        ?UploadedFile $upload,
        ?string $currentValue,
        bool $remove,
    ): ?string {
        if ($remove) {
            EmployeeCustomFieldFile::delete($currentValue);

            return null;
        }

        if ($upload instanceof UploadedFile) {
            EmployeeCustomFieldFile::delete($currentValue);

            return $this->storeUploadedFile($employee, $definition, $upload);
        }

        return $currentValue;
    }

    protected function uniqueKey(Organization $organization, string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'field';
        }

        $key = $base;
        $counter = 1;

        while (EmployeeFieldDefinition::query()
            ->where('organization_id', $organization->id)
            ->where('key', $key)
            ->exists()) {
            $key = $base.'_'.$counter;
            $counter++;
        }

        return $key;
    }

    protected function normalizeValue(EmployeeFieldDefinition $definition, mixed $value): ?string
    {
        if ($definition->type === CustomFieldType::Boolean) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
