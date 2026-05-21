<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class OrganizationContractTemplate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'body',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function documentTitle(?string $employeeName = null): string
    {
        if ($employeeName === null) {
            return $this->name;
        }

        return $this->name.' — '.$employeeName;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
