<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Models\Organization;
use App\Models\Position;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Position::class);

        return view('app.settings.positions', [
            'organization' => CurrentOrganization::check(),
            'positions' => Position::query()->withCount('employees')->orderBy('title')->get(),
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        Position::query()->create($request->validated());

        return back()->with('status', 'Position added successfully.');
    }

    public function destroy(Organization $organization, Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        $position->delete();

        return back()->with('status', 'Position removed successfully.');
    }
}
