<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeFieldDefinition extends Model
{
    use BelongsToOrganization;

    protected static function booted(): void
    {
        static::deleting(function (EmployeeFieldDefinition $definition): void {
            $definition->values()->with('definition')->get()->each->delete();
        });
    }

    protected $fillable = [
        'organization_id',
        'name',
        'key',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(EmployeeFieldValue::class);
    }
}
