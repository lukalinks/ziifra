<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'year',
        'month',
        'status',
        'rules_snapshot',
        'locked_by_user_id',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'status' => PayrollRunStatus::class,
            'rules_snapshot' => 'array',
            'locked_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function periodLabel(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public function periodSlug(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function getRouteKey(): string
    {
        return $this->periodSlug();
    }

    public function isDraft(): bool
    {
        return $this->status === PayrollRunStatus::Draft;
    }

    public function isLocked(): bool
    {
        return $this->status === PayrollRunStatus::Locked;
    }

    public function showUrl(): string
    {
        return $this->workspaceRoute('payroll.show', [
            'payrollRun' => $this->periodSlug(),
        ]);
    }
}
