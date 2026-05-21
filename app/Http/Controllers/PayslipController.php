<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayslipEmailService;
use App\Services\PayslipPdfService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function show(Organization $organization, PayrollRun $payrollRun, PayrollItem $item): View
    {
        $this->authorize('viewPayslip', $item);

        abort_unless($item->payroll_run_id === $payrollRun->id, 404);

        $organization = CurrentOrganization::check();
        $payrollRun->load('lockedBy');
        $item->loadMissing(['employee', 'allowanceLines']);

        $template = $organization->resolvedPayslipTemplate();
        $logoDataUri = ($template['show_logo'] ?? true)
            ? $organization->payslipLogoDataUri()
            : null;

        return view('app.payroll.payslip', [
            'organization' => $organization,
            'run' => $payrollRun,
            'item' => $item,
            'template' => $template,
            'logoDataUri' => $logoDataUri,
            'appName' => config('app.name'),
            'primaryColor' => $organization->primary_color ?? '#1e3a5f',
        ]);
    }

    public function pdf(
        Organization $organization,
        PayrollRun $payrollRun,
        PayrollItem $item,
        PayslipPdfService $payslipPdf,
    ): Response {
        $this->authorize('viewPayslip', $item);

        abort_unless($item->payroll_run_id === $payrollRun->id, 404);

        $organization = CurrentOrganization::check();

        return $payslipPdf->makePdf($organization, $payrollRun, $item)
            ->download($payslipPdf->suggestedFilename($payrollRun, $item));
    }

    public function sendEmail(
        Request $request,
        Organization $organization,
        PayrollRun $payrollRun,
        PayrollItem $item,
        PayslipEmailService $payslipEmail,
    ): RedirectResponse {
        $this->authorize('sendPayslipEmail', $item);

        abort_unless($item->payroll_run_id === $payrollRun->id, 404);

        $email = $item->payslipRecipientEmail();

        if ($email === null) {
            return redirect()
                ->to($payrollRun->showUrl())
                ->with('alert', [
                    'variant' => 'danger',
                    'title' => __('payroll.flash.payslip_email_failed_title'),
                    'body' => __('payroll.email_missing_recipient'),
                ]);
        }

        if ($payslipEmail->sendForItem($item, $request->user()) === 'failed') {
            return redirect()
                ->to($payrollRun->showUrl())
                ->with('alert', [
                    'variant' => 'danger',
                    'title' => __('payroll.flash.payslip_email_failed_title'),
                    'body' => __('payroll.email_send_failed', ['email' => $email]),
                ]);
        }

        return redirect()
            ->to($payrollRun->showUrl())
            ->with('alert', [
                'variant' => 'success',
                'title' => __('payroll.flash.payslip_email_sent_title'),
                'body' => __('payroll.email_sent_single', ['email' => $email]),
            ]);
    }
}
