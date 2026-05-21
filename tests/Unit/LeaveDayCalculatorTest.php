<?php

namespace Tests\Unit;

use App\Services\RegisterOrganizationService;
use App\Support\LeaveDayCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDayCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_weekdays_in_range(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@calc.test',
            'password123',
            'Calc Co',
        );
        $organization = $result['organization'];
        $organization->update(['work_week_days' => ['mon', 'tue', 'wed', 'thu', 'fri']]);

        $days = LeaveDayCalculator::countDays($organization, '2026-06-01', '2026-06-07');

        $this->assertSame(5.0, $days);
    }
}
