<?php

namespace App\Services;

use App\Enums\DailyHoursApprovalStatus;
use App\Enums\InvoiceSource;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class InvoiceFromHoursService
{
    public function __construct(
        protected DailyHoursService $hours,
        protected EmployeeRateService $rates,
        protected InvoiceService $invoices,
    ) {}

    public function createFromProjectHours(
        Organization $organization,
        Project $project,
        Carbon $start,
        Carbon $end,
        User $user,
        ?string $clientName = null,
    ): Invoice {
        $entries = $this->hours->approvedEntriesForPeriod(
            $organization->id,
            $start,
            $end,
            $project->id,
        );

        $lineItems = [];
        $subtotal = 0.0;

        foreach ($entries->groupBy('employee_id') as $employeeId => $group) {
            $employee = $group->first()->employee;
            $totalHours = round((float) $group->sum('hours'), 2);
            $rate = $this->rates->hourlyRateFor($employee, $start);
            $amount = round($totalHours * $rate, 2);
            $subtotal += $amount;

            $lineItems[] = [
                'employee_id' => $employeeId,
                'employee_name' => $employee->fullName(),
                'employee_code' => $employee->displayCode(),
                'hours' => $totalHours,
                'hourly_rate' => $rate,
                'amount' => $amount,
            ];
        }

        return Invoice::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'invoice_number' => $this->invoices->nextInvoiceNumber($organization),
            'client_name' => $clientName ?: $project->name,
            'title' => __('invoices.from_hours_title', [
                'project' => $project->name,
                'period' => $start->format('M j').' – '.$end->format('M j, Y'),
            ]),
            'amount' => $subtotal,
            'tax_percent' => 0,
            'currency' => $project->currency ?? ($organization->currency ?? 'EUR'),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'status' => InvoiceStatus::Draft,
            'source' => InvoiceSource::Hours,
            'line_items' => $lineItems,
        ]);
    }
}
