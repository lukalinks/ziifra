<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'employee_id',
        'base_gross_salary',
        'allowances',
        'exempt_allowances_total',
        'gross_salary',
        'employee_pension',
        'employer_pension',
        'income_tax',
        'net_salary',
        'employee_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'base_gross_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'exempt_allowances_total' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'employee_pension' => 'decimal:2',
            'employer_pension' => 'decimal:2',
            'income_tax' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'employee_snapshot' => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Labeled allowance rows for this pay period (taxable gross components and statutory-exempt cash).
     *
     * @return HasMany<PayrollItemAllowance, $this>
     */
    public function allowanceLines(): HasMany
    {
        return $this->hasMany(PayrollItemAllowance::class)->orderBy('sort_order');
    }

    public function employeeName(): string
    {
        return $this->employee_snapshot['name'] ?? $this->employee?->fullName() ?? 'Employee';
    }

    /**
     * Email address to use for payslip delivery (snapshot at lock time, else current employee profile).
     */
    public function payslipRecipientEmail(): ?string
    {
        $snapshot = $this->employee_snapshot ?? [];
        $fromSnapshot = $snapshot['email'] ?? null;

        if (is_string($fromSnapshot) && $fromSnapshot !== '') {
            $fromSnapshot = trim($fromSnapshot);

            return filter_var($fromSnapshot, FILTER_VALIDATE_EMAIL) ? $fromSnapshot : null;
        }

        $this->loadMissing('employee');

        $fromEmployee = $this->employee?->email;

        if (! is_string($fromEmployee) || $fromEmployee === '') {
            return null;
        }

        $fromEmployee = trim($fromEmployee);

        return filter_var($fromEmployee, FILTER_VALIDATE_EMAIL) ? $fromEmployee : null;
    }

    protected function workspaceRouteParameter(): string
    {
        return 'item';
    }

    public function payslipUrl(): string
    {
        $run = $this->relationLoaded('payrollRun')
            ? $this->payrollRun
            : $this->payrollRun()->firstOrFail();

        return $this->workspaceRoute('payroll.payslip', [
            'payrollRun' => $run,
        ]);
    }

    public function payslipPdfUrl(): string
    {
        $run = $this->relationLoaded('payrollRun')
            ? $this->payrollRun
            : $this->payrollRun()->firstOrFail();

        return $this->workspaceRoute('payroll.payslip.pdf', [
            'payrollRun' => $run,
        ]);
    }
}
