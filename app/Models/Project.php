<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class)->orderBy('sort_order')->orderBy('id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_employee')->withTimestamps();
    }

    public function dailyHoursEntries(): HasMany
    {
        return $this->hasMany(DailyHoursEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->latest('uploaded_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function completionPercent(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->tasks()->where('status', ProjectTaskStatus::Done)->count();

        return (int) round(($done / $total) * 100);
    }
}
