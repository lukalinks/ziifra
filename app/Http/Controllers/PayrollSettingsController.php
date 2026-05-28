<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePayrollSettingsRequest;
use App\Support\CurrentOrganization;
use App\Support\OrganizationLogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollSettingsController extends Controller
{
    public function edit(): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        return view('app.settings.payroll', [
            'organization' => $organization,
            'payrollSettings' => $organization->resolvedPayrollSettings(),
            'payslipTpl' => $organization->resolvedPayslipTemplate(),
            'hasPayroll' => true,
        ]);
    }

    public function update(UpdatePayrollSettingsRequest $request): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $data = $request->validated();

        $organization->fill([
            'signatory_name' => $data['signatory_name'] ?? null,
            'signatory_title' => $data['signatory_title'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_iban' => $data['bank_iban'] ?? null,
            'payroll_settings' => $data['payroll_settings'] ?? [],
            'payslip_template' => $data['payslip_template'] ?? $organization->payslip_template,
        ]);

        if ($request->boolean('remove_logo')) {
            OrganizationLogo::delete($organization->logo_path);
            $organization->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            OrganizationLogo::delete($organization->logo_path);
            $organization->logo_path = OrganizationLogo::store($organization, $request->file('logo'));
        }

        $organization->save();

        return redirect()
            ->route('settings.payroll.edit')
            ->with('status', __('settings.payroll.saved'));
    }
}
