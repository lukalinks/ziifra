<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Support\EmployeeCustomFieldFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFieldValue extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_field_definition_id',
        'value',
    ];

    protected static function booted(): void
    {
        static::deleting(function (EmployeeFieldValue $value): void {
            $value->loadMissing('definition');

            if ($value->definition?->type === CustomFieldType::File) {
                EmployeeCustomFieldFile::delete($value->value);
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(EmployeeFieldDefinition::class, 'employee_field_definition_id');
    }

    public function displayValue(): string
    {
        $definition = $this->definition;
        $value = $this->value;

        if ($value === null || $value === '') {
            return '—';
        }

        return match ($definition->type) {
            CustomFieldType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No',
            CustomFieldType::Date => \Carbon\Carbon::parse($value)->format('M j, Y'),
            CustomFieldType::File => $this->fileMetadata()['name'] ?? '—',
            default => $value,
        };
    }

    public function isFile(): bool
    {
        return $this->definition->type === CustomFieldType::File;
    }

    /**
     * @return array{path: string, name: string}|null
     */
    public function fileMetadata(): ?array
    {
        return EmployeeCustomFieldFile::decode($this->value);
    }

    public function hasStoredFile(): bool
    {
        return EmployeeCustomFieldFile::exists($this->value);
    }
}
