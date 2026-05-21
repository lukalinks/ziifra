<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveCalendarService;
use App\Support\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveCalendarController extends Controller
{
    public function __invoke(Request $request, LeaveCalendarService $calendar): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $organization = CurrentOrganization::check();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $showPending = $request->boolean('pending', true);

        return view('app.leave.calendar', [
            'organization' => $organization,
            'calendar' => $calendar->build($organization, $request->user(), $year, $month, $showPending),
            'canCreate' => $request->user()->can('create', LeaveRequest::class),
        ]);
    }
}
