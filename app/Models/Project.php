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

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->project_code)) {
                $project->project_code = static::generateUniqueCode((int) $project->organization_id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'project_code';
    }

    public function getRouteKey(): string
    {
        return $this->project_code ?? (string) $this->getKey();
    }

    public static function generateUniqueCode(int $organizationId, ?int $exceptId = null): string
    {
        $maxSequence = static::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('project_code')
            ->pluck('project_code')
            ->map(function (string $code): int {
                if (preg_match('/^PRJ-(\d+)$/i', $code, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        $sequence = $maxSequence + 1;

        do {
            $code = 'PRJ-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::query()
            ->where('organization_id', $organizationId)
            ->where('project_code', $code)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists());

        return $code;
    }

    public function displayCode(): string
    {
        return $this->project_code ?? (string) $this->getKey();
    }

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'name',
        'project_code',
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
