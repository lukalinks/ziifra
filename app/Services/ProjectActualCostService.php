<?php

namespace App\Services;

use App\Models\DailyHoursEntry;
use App\Models\Project;

class ProjectActualCostService
{
    public function __construct(
        protected EmployeeRateService $rates,
    ) {}

    /**
     * Labor (hours × rates for all time) plus optional amounts on project documents.
     *
     * @return array{labor: float, documents: float, total: float, currency: string, formatted: string}
     */
    public function forProject(Project $project): array
    {
        $currency = $project->currency ?? $project->organization?->currency ?? 'EUR';
        $labor = $this->laborCost($project);
        $documents = $this->documentSpend($project);
        $total = round($labor + $documents, 2);

        return [
            'labor' => round($labor, 2),
            'documents' => round($documents, 2),
            'total' => $total,
            'currency' => $currency,
            'formatted' => $this->formatAmount($total, $currency),
        ];
    }

    public function formatAmount(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 2, '.', '\'');

        return "- {$formatted} .- {$currency}";
    }

    protected function laborCost(Project $project): float
    {
        $cost = 0.0;

        $entries = DailyHoursEntry::query()
            ->where('project_id', $project->id)
            ->where('hours', '>', 0)
            ->with('employee')
            ->get();

        foreach ($entries as $entry) {
            $employee = $entry->employee;
            if ($employee === null) {
                continue;
            }

            $rate = $this->rates->hourlyRateFor($employee, $entry->work_date);
            $cost += (float) $entry->hours * $rate;
        }

        return $cost;
    }

    protected function documentSpend(Project $project): float
    {
        return (float) $project->documents()
            ->whereNotNull('amount')
            ->sum('amount');
    }
}
