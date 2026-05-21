<?php

namespace App\Services;

class KosovoPayrollCalculator
{
    /**
     * @param  array<string, mixed>|null  $rules
     * @return array{
     *     gross_salary: float,
     *     employee_pension: float,
     *     employer_pension: float,
     *     income_tax: float,
     *     net_salary: float,
     * }
     */
    public function calculate(float $gross, ?array $rules = null): array
    {
        $rules ??= config('payroll.kosovo', []);
        $gross = round(max(0, $gross), 2);

        $employeePensionRate = (float) ($rules['employee_pension_rate'] ?? 0.05);
        $employerPensionRate = (float) ($rules['employer_pension_rate'] ?? 0.05);

        $employeePension = round($gross * $employeePensionRate, 2);
        $employerPension = round($gross * $employerPensionRate, 2);
        $taxable = max(0, $gross - $employeePension);
        $incomeTax = $this->progressiveTax($taxable, $rules['monthly_tax_brackets'] ?? []);
        $net = round($gross - $employeePension - $incomeTax, 2);

        return [
            'gross_salary' => $gross,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'income_tax' => $incomeTax,
            'net_salary' => $net,
        ];
    }

    /**
     * @param  list<array{up_to: float|null, rate: float}>  $brackets
     */
    public function progressiveTax(float $taxable, array $brackets): float
    {
        if ($taxable <= 0 || $brackets === []) {
            return 0.0;
        }

        $tax = 0.0;
        $previousLimit = 0.0;

        foreach ($brackets as $bracket) {
            $limit = $bracket['up_to'] ?? null;
            $rate = (float) ($bracket['rate'] ?? 0);

            if ($limit === null) {
                $band = max(0, $taxable - $previousLimit);
            } else {
                $band = max(0, min($taxable, (float) $limit) - $previousLimit);
            }

            if ($band > 0) {
                $tax += $band * $rate;
            }

            if ($limit !== null) {
                $previousLimit = (float) $limit;
                if ($taxable <= $previousLimit) {
                    break;
                }
            } else {
                break;
            }
        }

        return round($tax, 2);
    }
}
