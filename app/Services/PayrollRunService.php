<?php

namespace App\Services;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollAllowanceKind;
use App\Enums\PayrollRunStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollRunService
{
    public function __construct(
        protected KosovoPayrollCalculator $calculator,
    ) {}

    public function create(Organization $organization, int $year, int $month): PayrollRun
    {
        if (PayrollRun::query()
            ->where('organization_id', $organization->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists()) {
            throw ValidationException::withMessages([
                'month' => __('payroll.errors.period_exists'),
            ]);
        }

        return DB::transaction(function () use ($organization, $year, $month): PayrollRun {
            $run = PayrollRun::query()->create([
                'organization_id' => $organization->id,
                'year' => $year,
                'month' => $month,
                'status' => PayrollRunStatus::Draft,
                'rules_snapshot' => config('payroll.kosovo'),
            ]);

            $employees = Employee::query()
                ->where('organization_id', $organization->id)
                ->where('employment_status', EmploymentStatus::Active)
                ->with(['employeeAllowances' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            foreach ($employees as $employee) {
                $this->upsertItem(
                    $run,
                    $employee,
                    (float) ($employee->gross_salary ?? 0),
                );
            }

            return $run->load(['items.employee', 'items.allowanceLines']);
        });
    }

    /**
     * Recompute deductions and net pay from persisted allowance rows and base gross (for demo seeding / repairs).
     */
    public function recalculateDraftItem(PayrollItem $item): void
    {
        $item->load(['allowanceLines', 'payrollRun']);

        $lines = $item->allowanceLines->map(fn ($l) => [
            'label' => $l->label,
            'amount' => (float) $l->amount,
            'tax_treatment' => $l->tax_treatment,
            'kind' => $l->kind,
            'notes' => $l->notes,
        ])->all();

        $this->applyCalculationFromLines($item, (float) $item->base_gross_salary, $lines);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $itemPayloadById  payroll_item_id => fields
     */
    public function updateDraftItems(PayrollRun $run, array $itemPayloadById): PayrollRun
    {
        if (! $run->isDraft()) {
            throw ValidationException::withMessages([
                'payroll' => __('payroll.errors.run_locked'),
            ]);
        }

        foreach ($itemPayloadById as $itemId => $payload) {
            $item = $run->items()->whereKey($itemId)->first();

            if ($item === null || ! is_array($payload)) {
                continue;
            }

            $item->loadMissing('payrollRun');

            $hasSplit = array_key_exists('base_gross_salary', $payload)
                || array_key_exists('allowances', $payload);

            $base = array_key_exists('base_gross_salary', $payload)
                ? (float) $payload['base_gross_salary']
                : (float) $item->base_gross_salary;

            if (! $hasSplit && array_key_exists('gross_salary', $payload)) {
                $base = (float) $payload['gross_salary'];
            }

            $lines = null;

            if (isset($payload['allowance_lines']) && is_array($payload['allowance_lines'])) {
                $lines = $this->normalizeAllowanceLinesPayload($payload['allowance_lines']);
            } elseif ($hasSplit && array_key_exists('allowances', $payload) && ! array_key_exists('allowance_lines', $payload)) {
                $allow = round(max(0, (float) $payload['allowances']), 2);
                $lines = $allow > 0
                    ? [[
                        'label' => __('payroll.allowance_aggregate_label'),
                        'amount' => $allow,
                        'tax_treatment' => AllowanceTaxTreatment::Taxable,
                        'kind' => PayrollAllowanceKind::Recurring,
                        'notes' => null,
                    ]]
                    : [];
            } elseif (! $hasSplit && array_key_exists('gross_salary', $payload)) {
                $lines = [];
            }

            if ($lines === null) {
                $item->load('allowanceLines');
                $lines = $item->allowanceLines->map(fn ($l) => [
                    'label' => $l->label,
                    'amount' => (float) $l->amount,
                    'tax_treatment' => $l->tax_treatment,
                    'kind' => $l->kind,
                    'notes' => $l->notes,
                ])->all();
            }

            $this->persistAllowanceLines($item, $lines);
            $this->applyCalculationFromLines($item, $base, $lines);
        }

        return $run->fresh(['items.employee', 'items.allowanceLines']);
    }

    public function lock(PayrollRun $run, User $user): PayrollRun
    {
        if (! $run->isDraft()) {
            throw ValidationException::withMessages([
                'payroll' => __('payroll.errors.run_locked'),
            ]);
        }

        $run->update([
            'status' => PayrollRunStatus::Locked,
            'locked_at' => now(),
            'locked_by_user_id' => $user->id,
            'rules_snapshot' => config('payroll.kosovo'),
        ]);

        return $run->fresh(['items.employee', 'items.allowanceLines', 'lockedBy']);
    }

    protected function upsertItem(PayrollRun $run, Employee $employee, float $baseGross): PayrollItem
    {
        $lines = $this->allowanceLinesFromEmployee($employee);

        $item = PayrollItem::query()->updateOrCreate(
            [
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
            ],
            [
                'organization_id' => $run->organization_id,
                'employee_snapshot' => $this->employeeSnapshot($employee),
                'base_gross_salary' => 0,
                'allowances' => 0,
                'exempt_allowances_total' => 0,
                'gross_salary' => 0,
                'employee_pension' => 0,
                'employer_pension' => 0,
                'income_tax' => 0,
                'net_salary' => 0,
            ],
        );

        $this->persistAllowanceLines($item, $lines);
        $this->applyCalculationFromLines($item, $baseGross, $lines);

        return $item->fresh(['allowanceLines']);
    }

    /**
     * @return list<array{label: string, amount: float, tax_treatment: AllowanceTaxTreatment, kind: PayrollAllowanceKind, notes: ?string, sort_order?: int}>
     */
    protected function allowanceLinesFromEmployee(Employee $employee): array
    {
        $employee->loadMissing(['employeeAllowances' => fn ($q) => $q->orderBy('sort_order')]);

        $rows = [];

        foreach ($employee->employeeAllowances as $i => $allowance) {
            $rows[] = [
                'label' => $allowance->label,
                'amount' => (float) $allowance->amount,
                'tax_treatment' => $allowance->tax_treatment,
                'kind' => PayrollAllowanceKind::Recurring,
                'notes' => null,
                'sort_order' => $i,
            ];
        }

        if ($rows === [] && (float) ($employee->monthly_allowances ?? 0) > 0) {
            $rows[] = [
                'label' => __('payroll.legacy_monthly_allowance_line'),
                'amount' => round((float) $employee->monthly_allowances, 2),
                'tax_treatment' => AllowanceTaxTreatment::Taxable,
                'kind' => PayrollAllowanceKind::Recurring,
                'notes' => null,
                'sort_order' => 0,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{label: string, amount: float, tax_treatment: AllowanceTaxTreatment, kind: PayrollAllowanceKind, notes: ?string, sort_order?: int}>  $lines
     */
    protected function persistAllowanceLines(PayrollItem $item, array $lines): void
    {
        $item->allowanceLines()->delete();

        foreach (array_values($lines) as $i => $line) {
            $item->allowanceLines()->create([
                'label' => $line['label'],
                'amount' => round(max(0, (float) $line['amount']), 2),
                'tax_treatment' => $line['tax_treatment'],
                'kind' => $line['kind'],
                'notes' => $line['notes'] ?? null,
                'sort_order' => (int) ($line['sort_order'] ?? $i),
            ]);
        }
    }

    /**
     * @param  list<array{label?: string, amount?: float|int|string, tax_treatment?: mixed, kind?: mixed, notes?: string|null}>  $raw
     * @return list<array{label: string, amount: float, tax_treatment: AllowanceTaxTreatment, kind: PayrollAllowanceKind, notes: ?string}>
     */
    public function normalizeAllowanceLinesPayload(array $raw): array
    {
        $out = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $amount = round(max(0, (float) ($row['amount'] ?? 0)), 2);

            if ($label === '' && $amount <= 0) {
                continue;
            }

            if ($label === '') {
                $label = __('payroll.allowance_default_label');
            }

            $taxTreatment = AllowanceTaxTreatment::tryFrom((string) ($row['tax_treatment'] ?? ''))
                ?? AllowanceTaxTreatment::Taxable;

            $kind = PayrollAllowanceKind::tryFrom((string) ($row['kind'] ?? ''))
                ?? PayrollAllowanceKind::OneOff;

            $notes = isset($row['notes']) ? trim((string) $row['notes']) : null;
            if ($notes === '') {
                $notes = null;
            }

            $out[] = [
                'label' => $label,
                'amount' => $amount,
                'tax_treatment' => $taxTreatment,
                'kind' => $kind,
                'notes' => $notes,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{label: string, amount: float, tax_treatment: AllowanceTaxTreatment, kind: PayrollAllowanceKind, notes?: ?string}>  $lines
     */
    protected function applyCalculationFromLines(PayrollItem $item, float $baseGross, array $lines): void
    {
        $baseGross = round(max(0, $baseGross), 2);
        $totals = $this->totalsFromLines($lines);
        $taxableGross = round($baseGross + $totals['taxable'], 2);

        $rules = $item->payrollRun->rules_snapshot ?? config('payroll.kosovo');
        $calculated = $this->calculator->calculate($taxableGross, is_array($rules) ? $rules : null);

        $net = round((float) $calculated['net_salary'] + $totals['exempt'], 2);

        $item->update(array_merge($calculated, [
            'base_gross_salary' => $baseGross,
            'allowances' => $totals['taxable'],
            'exempt_allowances_total' => $totals['exempt'],
            'net_salary' => $net,
        ]));
    }

    /**
     * @param  list<array{amount: float, tax_treatment: AllowanceTaxTreatment}>  $lines
     * @return array{taxable: float, exempt: float}
     */
    protected function totalsFromLines(array $lines): array
    {
        $taxable = 0.0;
        $exempt = 0.0;

        foreach ($lines as $line) {
            $amt = round(max(0, (float) ($line['amount'] ?? 0)), 2);
            $tr = $line['tax_treatment'] ?? AllowanceTaxTreatment::Taxable;

            if ($tr === AllowanceTaxTreatment::Taxable) {
                $taxable += $amt;
            } else {
                $exempt += $amt;
            }
        }

        return ['taxable' => round($taxable, 2), 'exempt' => round($exempt, 2)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeSnapshot(Employee $employee): array
    {
        $employee->loadMissing([
            'department',
            'position',
            'employeeAllowances' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return [
            'name' => $employee->fullName(),
            'email' => $employee->email,
            'department' => $employee->department?->name,
            'position' => $employee->position?->title,
            'base_gross_salary' => (float) ($employee->gross_salary ?? 0),
            'monthly_allowances' => (float) ($employee->monthly_allowances ?? 0),
            'allowance_templates' => $employee->employeeAllowances->map(fn ($a) => [
                'label' => $a->label,
                'amount' => (float) $a->amount,
                'tax_treatment' => $a->tax_treatment->value,
            ])->values()->all(),
        ];
    }
}
