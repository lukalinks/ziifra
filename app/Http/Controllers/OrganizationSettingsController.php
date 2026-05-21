<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Enums\OrganizationLegalForm;
use App\Enums\WorkWeekDay;
use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Models\Organization;
use App\Services\OrganizationBillingService;
use App\Services\OrganizationSettingsService;
use App\Support\CurrentOrganization;
use App\Support\KosovoPublicHolidays;
use App\Support\OrganizationLogo;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationSettingsController extends Controller
{
    public function edit(): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        return view('app.settings.company', [
            'organization' => $organization,
            'legalForms' => OrganizationLegalForm::cases(),
            'timezones' => Organization::supportedTimezones(),
            'currencies' => Organization::supportedCurrencies(),
            'locales' => Organization::supportedLocales(),
            'countries' => Organization::supportedCountries(),
            'workWeekDays' => WorkWeekDay::cases(),
            'dateFormats' => Organization::supportedDateFormats(),
            'fiscalYearMonths' => Organization::supportedFiscalYearMonths(),
            'employmentTypes' => EmploymentType::cases(),
            'kosovoHolidayNames' => KosovoPublicHolidays::previewNames(),
            'workspaceUrl' => Workspace::route('dashboard', $organization),
            'hasPayroll' => app(OrganizationBillingService::class)->hasPayroll($organization),
        ]);
    }

    public function update(
        UpdateOrganizationSettingsRequest $request,
        OrganizationSettingsService $settings,
    ): RedirectResponse {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $previousSlug = $organization->slug;

        $organization = $settings->update(
            $organization,
            $request->safe()->except(['logo', 'remove_logo']),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        $message = 'Company settings saved successfully.';
        if ($previousSlug !== $organization->slug) {
            $message .= ' Workspace URL updated.';
        }

        return redirect()
            ->to(Workspace::route('settings.company.edit', $organization))
            ->with('status', $message);
    }

    public function logo(): StreamedResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('view', $organization);

        if (! OrganizationLogo::exists($organization->logo_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $organization->logo_path,
            'logo',
            ['Cache-Control' => 'private, max-age=3600'],
        );
    }
}
