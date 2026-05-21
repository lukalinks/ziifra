<?php

namespace Tests\Unit;

use App\Services\KosovoPayrollCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KosovoPayrollCalculatorTest extends TestCase
{
    #[DataProvider('grossExamples')]
    public function test_calculates_kosovo_deductions(float $gross): void
    {
        $calculator = app(KosovoPayrollCalculator::class);
        $result = $calculator->calculate($gross);

        $this->assertSame($gross, $result['gross_salary']);
        $this->assertSame(round($gross * 0.05, 2), $result['employee_pension']);
        $this->assertSame(
            round($gross - $result['employee_pension'] - $result['income_tax'], 2),
            $result['net_salary'],
        );
    }

    /**
     * @return array<string, array{0: float}>
     */
    public static function grossExamples(): array
    {
        return [
            'zero' => [0.0],
            'below first bracket' => [500.0],
            'mid range' => [1200.0],
        ];
    }

    public function test_progressive_tax_follows_monthly_slices_aligned_with_pwc_annual_bands(): void
    {
        $calculator = app(KosovoPayrollCalculator::class);
        $brackets = config('payroll.kosovo.monthly_tax_brackets');

        // Within first €250/month slice → 0%
        $this->assertSame(0.0, $calculator->progressiveTax(170, $brackets));
        // €250–€450/month slice @ 8%
        $this->assertSame(4.0, $calculator->progressiveTax(300, $brackets));
        // Above €450/month → remainder @ 10%
        $this->assertSame(21.0, $calculator->progressiveTax(500, $brackets));
    }

    public function test_example_taxable_after_pension_matches_expected(): void
    {
        $calculator = app(KosovoPayrollCalculator::class);
        $result = $calculator->calculate(1000.00);

        // Pension 5%; taxable €950 → €700 @ 0%, €200 @ 8%, €500 @ 10% → €66 tax
        $this->assertSame(50.0, $result['employee_pension']);
        $this->assertSame(66.0, $result['income_tax']);
        $this->assertSame(884.0, $result['net_salary']);
    }
}
