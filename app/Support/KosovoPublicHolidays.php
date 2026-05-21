<?php

namespace App\Support;

/**
 * Fixed-date Kosovo public holidays for calendar preview.
 * Variable Islamic holidays and movable Orthodox Easter are added in a later phase.
 */
class KosovoPublicHolidays
{
    /**
     * @return list<array{name: string, date: string}>
     */
    public static function fixedHolidaysForYear(int $year): array
    {
        return [
            ['name' => 'New Year\'s Day', 'date' => "{$year}-01-01"],
            ['name' => 'New Year (Day 2)', 'date' => "{$year}-01-02"],
            ['name' => 'Orthodox Christmas', 'date' => "{$year}-01-07"],
            ['name' => 'Independence Day', 'date' => "{$year}-02-17"],
            ['name' => 'Constitution Day', 'date' => "{$year}-04-09"],
            ['name' => 'Labour Day', 'date' => "{$year}-05-01"],
            ['name' => 'Europe Day', 'date' => "{$year}-05-09"],
            ['name' => 'Christmas Day', 'date' => "{$year}-12-25"],
        ];
    }

    /**
     * @return list<string>
     */
    public static function previewNames(): array
    {
        return array_map(
            fn (array $holiday): string => $holiday['name'],
            self::fixedHolidaysForYear((int) now()->format('Y')),
        );
    }
}
