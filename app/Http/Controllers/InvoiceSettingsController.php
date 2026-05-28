<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInvoiceSettingsRequest;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvoiceSettingsController extends Controller
{
    public function edit(): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        return view('app.settings.invoices', [
            'organization' => $organization,
            'invoiceSettings' => $organization->resolvedInvoiceSettings(),
        ]);
    }

    public function update(UpdateInvoiceSettingsRequest $request): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $data = $request->validated();

        $organization->update([
            'bank_name' => $data['bank_name'] ?? $organization->bank_name,
            'bank_iban' => $data['bank_iban'] ?? $organization->bank_iban,
            'invoice_settings' => $data['invoice_settings'],
        ]);

        return redirect()
            ->route('settings.invoices.edit')
            ->with('status', __('settings.invoices.saved'));
    }
}
