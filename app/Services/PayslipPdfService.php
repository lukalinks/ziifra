<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PayslipPdfService
{
    /**
     * Build a DomPDF instance for one payslip (HTML → PDF).
     */
    public function makePdf(Organization $organization, PayrollRun $run, PayrollItem $item): \Barryvdh\DomPDF\PDF
    {
        $template = $organization->resolvedPayslipTemplate();
        $logoDataUri = ($template['show_logo'] ?? true)
            ? $organization->payslipLogoDataUri()
            : null;

        return Pdf::loadView('app.payroll.payslip.pdf', [
            'organization' => $organization,
            'run' => $run,
            'item' => $item->loadMissing('allowanceLines'),
            'template' => $template,
            'logoDataUri' => $logoDataUri,
            'appName' => config('app.name'),
            'primaryColor' => $organization->primary_color ?? '#1e3a5f',
        ])->setPaper('a4', 'portrait');
    }

    public function suggestedFilename(PayrollRun $run, PayrollItem $item): string
    {
        return 'payslip-'.Str::slug($item->employeeName()).'-'.$run->year.'-'.str_pad((string) $run->month, 2, '0', STR_PAD_LEFT).'.pdf';
    }

    /**
     * Unique filename inside a ZIP archive (handles duplicate names).
     */
    public function zipEntryFilename(PayrollRun $run, PayrollItem $item): string
    {
        return Str::slug($item->employeeName()).'-'.$item->id.'-'.$run->year.'-'.str_pad((string) $run->month, 2, '0', STR_PAD_LEFT).'.pdf';
    }
}
