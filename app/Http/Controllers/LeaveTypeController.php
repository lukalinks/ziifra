<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LeaveType::class);

        return view('app.settings.leave-types', [
            'organization' => CurrentOrganization::check(),
            'leaveTypes' => LeaveType::query()
                ->withCount('requests')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $sortOrder = LeaveType::query()->where('organization_id', $organization->id)->max('sort_order');

        LeaveType::query()->create([
            ...$request->validated(),
            'organization_id' => $organization->id,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);

        return back()->with('status', 'Leave type added successfully.');
    }

    public function destroy(Organization $organization, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);

        if ($leaveType->requests()->exists()) {
            return back()->withErrors([
                'leave_type' => 'Cannot remove a leave type that has requests. Cancel or process them first.',
            ]);
        }

        $leaveType->delete();

        return back()->with('status', 'Leave type removed successfully.');
    }
}
