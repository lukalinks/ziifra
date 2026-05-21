<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Enums\WorkWeekDay;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use App\Support\KosovoPublicHolidays;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class LeaveCalendarService
{
    public function __construct(
        protected LeaveAuthorizationService $leaveAuth,
    ) {}

    /**
     * @return array{
     *     year: int,
     *     month: int,
     *     monthLabel: string,
     *     prev: array{year: int, month: int},
     *     next: array{year: int, month: int},
     *     showPending: bool,
     *     weeks: list<list<array{
     *         date: ?Carbon,
     *         inMonth: bool,
     *         isWorkday: bool,
     *         holiday: ?string,
     *         entries: list<array{
     *             id: int,
     *             employeeName: string,
     *             leaveType: string,
     *             status: LeaveRequestStatus,
     *         }>
     *     }>>,
     * }
     */
    public function build(
        Organization $organization,
        User $user,
        int $year,
        int $month,
        bool $showPending = true,
    ): array {
        $month = max(1, min(12, $month));
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $statuses = [LeaveRequestStatus::Approved];
        if ($showPending) {
            $statuses[] = LeaveRequestStatus::Pending;
        }

        $requests = $this->leaveAuth->scopeVisibleTo(
            LeaveRequest::query()
                ->with(['employee', 'leaveType'])
                ->whereIn('status', array_map(fn (LeaveRequestStatus $s) => $s->value, $statuses))
                ->where('start_date', '<=', $gridEnd->toDateString())
                ->where('end_date', '>=', $gridStart->toDateString()),
            $user,
            $organization,
        )->get();

        $entriesByDate = $this->indexEntriesByDate($requests, $gridStart, $gridEnd);
        $holidays = [];
        if ($organization->observe_kosovo_holidays) {
            foreach (array_unique([(int) $gridStart->year, (int) $gridEnd->year]) as $holidayYear) {
                $holidays = array_merge($holidays, $this->holidaysByDate($holidayYear));
            }
        }
        $workWeek = $organization->workWeekDayValues();

        $weeks = [];
        $week = [];

        foreach (CarbonPeriod::create($gridStart, $gridEnd) as $date) {
            $dateKey = $date->toDateString();

            $week[] = [
                'date' => $date->copy(),
                'inMonth' => $date->month === $month,
                'isWorkday' => $this->isWorkday($date, $workWeek),
                'holiday' => $holidays[$dateKey] ?? null,
                'entries' => $entriesByDate[$dateKey] ?? [],
            ];

            if ($date->dayOfWeekIso === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if ($week !== []) {
            $weeks[] = $week;
        }

        $prev = $monthStart->copy()->subMonth();
        $next = $monthStart->copy()->addMonth();

        return [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $monthStart->locale(app()->getLocale())->translatedFormat('F Y'),
            'prev' => ['year' => (int) $prev->year, 'month' => (int) $prev->month],
            'next' => ['year' => (int) $next->year, 'month' => (int) $next->month],
            'showPending' => $showPending,
            'weeks' => $weeks,
        ];
    }

    /**
     * @param  Collection<int, LeaveRequest>  $requests
     * @return array<string, list<array{id: int, employeeName: string, leaveType: string, status: LeaveRequestStatus}>>
     */
    protected function indexEntriesByDate(Collection $requests, Carbon $gridStart, Carbon $gridEnd): array
    {
        $entriesByDate = [];

        foreach ($requests as $request) {
            $periodStart = $request->start_date->greaterThan($gridStart)
                ? $request->start_date->copy()
                : $gridStart->copy();
            $periodEnd = $request->end_date->lessThan($gridEnd)
                ? $request->end_date->copy()
                : $gridEnd->copy();

            foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
                $key = $date->toDateString();
                $entriesByDate[$key] ??= [];
                $entriesByDate[$key][] = [
                    'id' => $request->id,
                    'employeeName' => $request->employee->fullName(),
                    'leaveType' => $request->leaveType->name,
                    'status' => $request->status,
                ];
            }
        }

        foreach ($entriesByDate as $key => $entries) {
            usort($entries, fn (array $a, array $b): int => strcmp($a['employeeName'], $b['employeeName']));
            $entriesByDate[$key] = $entries;
        }

        return $entriesByDate;
    }

    /**
     * @return array<string, string>
     */
    protected function holidaysByDate(int $year): array
    {
        $map = [];

        foreach (KosovoPublicHolidays::fixedHolidaysForYear($year) as $holiday) {
            $map[$holiday['date']] = $holiday['name'];
        }

        return $map;
    }

    /**
     * @param  list<string>  $workWeek
     */
    protected function isWorkday(Carbon $date, array $workWeek): bool
    {
        $iso = $date->dayOfWeekIso;
        $value = match ($iso) {
            1 => WorkWeekDay::Mon->value,
            2 => WorkWeekDay::Tue->value,
            3 => WorkWeekDay::Wed->value,
            4 => WorkWeekDay::Thu->value,
            5 => WorkWeekDay::Fri->value,
            6 => WorkWeekDay::Sat->value,
            default => WorkWeekDay::Sun->value,
        };

        return in_array($value, $workWeek, true);
    }
}
