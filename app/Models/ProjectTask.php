<?php

namespace App\Models;

use App\Enums\ProjectTaskPriority;
use App\Enums\ProjectTaskStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'project_id',
        'assigned_employee_id',
        'title',
        'description',
        'status',
        'priority',
        'is_milestone',
        'due_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectTaskStatus::class,
            'priority' => ProjectTaskPriority::class,
            'is_milestone' => 'boolean',
            'due_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}
