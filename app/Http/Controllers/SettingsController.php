<?php

namespace App\Http\Controllers;

use App\Enums\PlanFeature;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(OrganizationBillingService $billing): View
    {
        $organization = CurrentOrganization::check();
        $role = auth()->user()->roleIn($organization);

        if (! ($role?->canManageEmployees() || $role?->canManageOrganization() || $role?->canManageLeave())) {
            abort(403);
        }

        return view('app.settings.index', [
            'organization' => $organization,
            'canManageOrganization' => $role?->canManageOrganization() ?? false,
            'canManageBilling' => $role?->canManageBilling() ?? false,
            'canManageEmployees' => $role?->canManageEmployees() ?? false,
            'canManageEmployeeFieldDefinitions' => $role?->canManageEmployeeFieldDefinitions() ?? false,
            'canManageLeave' => $role?->canManageLeave() ?? false,
            'canManageContractTemplates' => ($role?->canManageOrganization() ?? false)
                && $billing->hasFeature($organization, PlanFeature::Documents),
        ]);
    }
}
