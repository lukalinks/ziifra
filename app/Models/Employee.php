<?php

namespace App\Models;

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

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
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
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
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

    public function customFieldValueFor(EmployeeFieldDefinition $definition): ?EmployeeFieldValue
    {
        return $this->fieldValues
            ->firstWhere('employee_field_definition_id', $definition->id);
    }
}
