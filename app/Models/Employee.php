<?php

namespace App\Models;

use App\Enums\CompensationType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            if (blank($employee->employee_code)) {
                $employee->employee_code = static::generateUniqueCode((int) $employee->organization_id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'employee_code';
    }

    public function getRouteKey(): string
    {
        return $this->employee_code ?? (string) $this->getKey();
    }

    public static function generateUniqueCode(int $organizationId, ?int $exceptId = null): string
    {
        $maxSequence = static::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('employee_code')
            ->pluck('employee_code')
            ->map(function (string $code): int {
                if (preg_match('/^EMP-(\d+)$/i', $code, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        $sequence = $maxSequence + 1;

        do {
            $code = 'EMP-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::query()
            ->where('organization_id', $organizationId)
            ->where('employee_code', $code)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists());

        return $code;
    }

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'employee_code',
        'email',
        'phone',
        'department_id',
        'position_id',
        'manager_id',
        'user_id',
        'employment_type',
        'employment_status',
        'start_date',
        'gross_salary',
        'monthly_allowances',
        'compensation_type',
        'fixed_hourly_rate',
        'fixed_hourly_currency',
        'fixed_monthly_salary',
        'fixed_salary_currency',
        'trust_override_percent',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
            'compensation_type' => CompensationType::class,
            'fixed_hourly_rate' => 'decimal:2',
            'fixed_monthly_salary' => 'decimal:2',
            'trust_override_percent' => 'decimal:2',
            'start_date' => 'date',
            'gross_salary' => 'decimal:2',
            'monthly_allowances' => 'decimal:2',
            'terminated_at' => 'datetime',
        ];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->fullName())) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn (string $part) => mb_substr($part, 0, 1))->join(''));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(EmployeeFieldValue::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    /**
     * Recurring labeled allowances used when opening new payroll runs (tax treatment per line).
     *
     * @return HasMany<EmployeeAllowance, $this>
     */
    public function employeeAllowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class)->orderBy('sort_order');
    }

    public function hourlyRates(): HasMany
    {
        return $this->hasMany(EmployeeHourlyRate::class)->orderByDesc('year')->orderByDesc('month');
    }

    public function dailyHoursEntries(): HasMany
    {
        return $this->hasMany(DailyHoursEntry::class);
    }

    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_employee')->withTimestamps();
    }

    public function displayCode(): string
    {
        return $this->employee_code ?? (string) $this->id;
    }

    public function customFieldValueFor(EmployeeFieldDefinition $definition): ?EmployeeFieldValue
    {
        return $this->fieldValues
            ->firstWhere('employee_field_definition_id', $definition->id);
    }
}
