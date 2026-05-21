<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'recorded_by_user_id',
        'clock_in',
        'clock_out',
        'break_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'break_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->clock_out === null;
    }

    public function workedMinutes(): ?int
    {
        if ($this->clock_out === null) {
            return null;
        }

        $minutes = $this->clock_in->diffInMinutes($this->clock_out);

        return max(0, $minutes - $this->break_minutes);
    }

    public function workedHoursLabel(): string
    {
        $minutes = $this->workedMinutes();

        if ($minutes === null) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    public function isOvertimeDay(CarbonInterface $date, int $standardMinutes = 480): bool
    {
        $minutes = $this->workedMinutes();

        return $minutes !== null && $this->clock_in->isSameDay($date) && $minutes > $standardMinutes;
    }
}
