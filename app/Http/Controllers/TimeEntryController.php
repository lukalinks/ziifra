<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Services\EmployeeProfileService;
use App\Services\TimeAuthorizationService;
use App\Services\TimeEntryService;
use App\Services\TimesheetService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    public function __construct(
        protected TimeAuthorizationService $timeAuth,
        protected EmployeeProfileService $profiles,
        protected TimesheetService $timesheets,
    ) {}

    public function index(Request $request, TimeEntryService $timeEntries): View
    {
        $this->authorize('viewAny', TimeEntry::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();
        $standardMinutes = 480;

        $weekStart = $this->timesheets->weekStart($request->string('week')->toString() ?: null, $organization);
        $weekEnd = $this->timesheets->weekEnd($weekStart);
        $selectedEmployee = $this->timesheets->resolveEmployeeFilter($request, $user, $organization);
        $employeeId = $selectedEmployee?->id;

        $filterableEmployees = $this->timesheets->filterableEmployees($user, $organization);

        $entries = $this->timesheets
            ->entriesQuery($user, $organization, $weekStart, $weekEnd, $employeeId)
            ->get();

        $summary = $this->timesheets->summarize($entries, $standardMinutes);
        $entriesByDate = $entries->groupBy(fn (TimeEntry $entry) => $entry->clock_in->format('Y-m-d'));

        $linkedEmployee = $this->profiles->employeeFor($user, $organization);
        $openEntry = $linkedEmployee ? $timeEntries->openEntryFor($linkedEmployee) : null;
        $todayTotals = $linkedEmployee
            ? $timeEntries->dailyTotals($linkedEmployee, now()->toDateString(), $standardMinutes)
            : null;

        $clockableEmployees = $this->timeAuth->clockableEmployees($user, $organization);
        $canClockForOthers = $clockableEmployees->count() > 1
            || ($this->timeAuth->canViewAll($user, $organization) && $clockableEmployees->isNotEmpty());

        $indexQuery = $this->timesheets->indexQueryParams($weekStart, $selectedEmployee);

        return view('app.time.index', [
            'organization' => $organization,
            'entries' => $entries,
            'entriesByDate' => $entriesByDate,
            'summary' => $summary,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'employees' => $filterableEmployees,
            'clockableEmployees' => $clockableEmployees,
            'selectedEmployee' => $selectedEmployee,
            'indexQuery' => $indexQuery,
            'canClock' => $user->can('clock', TimeEntry::class),
            'canClockForOthers' => $canClockForOthers,
            'canManageEntries' => $user->can('create', TimeEntry::class),
            'showEmployeeColumn' => $filterableEmployees->count() > 1 && $selectedEmployee === null,
            'linkedEmployee' => $linkedEmployee,
            'openEntry' => $openEntry,
            'todayTotals' => $todayTotals,
            'standardHours' => intdiv($standardMinutes, 60),
        ]);
    }

    public function export(Request $request): Response
    {
        $this->authorize('viewAny', TimeEntry::class);

        $organization = CurrentOrganization::check();
        $user = $request->user();

        $weekStart = $this->timesheets->weekStart($request->string('week')->toString() ?: null, $organization);
        $weekEnd = $this->timesheets->weekEnd($weekStart);
        $selectedEmployee = $this->timesheets->resolveEmployeeFilter($request, $user, $organization);

        $export = $this->timesheets->buildCsvExport($user, $organization, $weekStart, $weekEnd, $selectedEmployee?->id);

        return response($export['content'], 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TimeEntry::class);

        $organization = CurrentOrganization::check();
        $selectedEmployee = $this->timesheets->resolveEmployeeFilter($request, $request->user(), $organization);

        return view('app.time.create', [
            'organization' => $organization,
            'employees' => $this->timeAuth->clockableEmployees($request->user(), $organization),
            'week' => $request->string('week')->toString(),
            'employeeId' => $selectedEmployee?->id,
            'indexQuery' => $this->timesheets->indexQueryParams(
                $this->timesheets->weekStart($request->string('week')->toString() ?: null, $organization),
                $selectedEmployee,
            ),
        ]);
    }

    public function store(Request $request, TimeEntryService $timeEntries): RedirectResponse
    {
        $this->authorize('create', TimeEntry::class);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'clock_in' => ['required', 'date'],
            'clock_out' => ['nullable', 'date', 'after:clock_in'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'week' => ['nullable', 'date'],
        ]);

        $timeEntries->storeManual($request->user(), $validated);

        $employee = Employee::query()->findOrFail($validated['employee_id']);
        $weekStart = $this->timesheets->weekStart($validated['week'] ?? null, CurrentOrganization::check());

        return redirect()
            ->route('time.index', $this->timesheets->indexQueryParams($weekStart, $employee))
            ->with('status', __('time.entry_created'));
    }

    public function edit(Organization $organization, TimeEntry $timeEntry): View
    {
        $this->authorize('update', $timeEntry);

        $timeEntry->load(['employee', 'recordedBy']);

        return view('app.time.edit', [
            'entry' => $timeEntry,
            'week' => request('week'),
        ]);
    }

    public function update(Request $request, Organization $organization, TimeEntry $timeEntry, TimeEntryService $timeEntries): RedirectResponse
    {
        $this->authorize('update', $timeEntry);

        $validated = $request->validate([
            'clock_in' => ['required', 'date'],
            'clock_out' => ['nullable', 'date', 'after:clock_in'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'week' => ['nullable', 'date'],
        ]);

        $timeEntries->updateEntry($request->user(), $timeEntry, $validated);

        $weekStart = $this->timesheets->weekStart($validated['week'] ?? null, $organization);

        return redirect()
            ->route('time.index', $this->timesheets->indexQueryParams($weekStart, $timeEntry->employee))
            ->with('status', __('time.entry_updated'));
    }

    public function destroy(Request $request, Organization $organization, TimeEntry $timeEntry, TimeEntryService $timeEntries): RedirectResponse
    {
        $this->authorize('delete', $timeEntry);

        $employee = $timeEntry->employee;
        $week = $request->string('week')->toString();

        $timeEntries->deleteEntry($request->user(), $timeEntry);

        $weekStart = $this->timesheets->weekStart($week ?: null, $organization);

        return redirect()
            ->route('time.index', $this->timesheets->indexQueryParams($weekStart, $employee))
            ->with('status', __('time.entry_deleted'));
    }

    public function clockIn(Request $request, TimeEntryService $timeEntries): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        $employeeId = $request->integer('employee_id') ?: null;
        $timeEntries->clockIn($request->user(), $employeeId);

        return redirect()
            ->route('time.index', $this->redirectIndexQuery($request))
            ->with('status', __('time.clocked_in'));
    }

    public function clockOut(Request $request, TimeEntryService $timeEntries): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employeeId = $validated['employee_id'] ?? null;
        $timeEntries->clockOut(
            $request->user(),
            $employeeId,
            isset($validated['break_minutes']) ? (int) $validated['break_minutes'] : null,
            $validated['notes'] ?? null,
        );

        return redirect()
            ->route('time.index', $this->redirectIndexQuery($request))
            ->with('status', __('time.clocked_out'));
    }

    /**
     * @return array{week?: string, employee?: string}
     */
    protected function redirectIndexQuery(Request $request): array
    {
        $organization = CurrentOrganization::check();
        $weekStart = $this->timesheets->weekStart($request->string('week')->toString() ?: null, $organization);

        $employee = null;
        if ($request->filled('employee_id')) {
            $employee = Employee::query()->find($request->integer('employee_id'));
        } elseif ($request->filled('employee')) {
            $employee = Employee::query()
                ->where('employee_code', $request->string('employee')->toString())
                ->first();
        }

        return $this->timesheets->indexQueryParams($weekStart, $employee);
    }
}
