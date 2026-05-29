<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMailSettingsRequest;
use App\Services\OrganizationMailService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailSettingsController extends Controller
{
    public function edit(OrganizationMailService $mail): View
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        return view('app.settings.mail', [
            'organization' => $organization,
            'mailSettings' => $mail->settingsForForm($organization),
            'mailStatus' => $mail->status($organization),
        ]);
    }

    public function update(
        UpdateMailSettingsRequest $request,
        OrganizationMailService $mail,
    ): RedirectResponse {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $organization->update([
            'mail_settings' => $mail->normalizeInput($organization, $request->input('mail_settings', [])),
        ]);

        return redirect()
            ->route('settings.mail.edit')
            ->with('status', __('settings.mail.saved'));
    }

    public function sendTest(Request $request, OrganizationMailService $mail): RedirectResponse
    {
        $organization = CurrentOrganization::check();
        $this->authorize('update', $organization);

        $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $mail->sendTest($organization, $request->string('test_email')->toString());
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['test_email' => __('settings.mail.test_failed')]);
        } catch (\InvalidArgumentException) {
            return back()
                ->withInput()
                ->withErrors(['test_email' => __('settings.mail.not_configured')]);
        }

        return redirect()
            ->route('settings.mail.edit')
            ->with('status', __('settings.mail.test_sent'));
    }
}
