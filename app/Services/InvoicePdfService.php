<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfService
{
    public function download(Invoice $invoice, Organization $organization): Response
    {
        $invoice->load(['project', 'createdBy']);

        $pdf = Pdf::loadView('app.invoices.pdf', [
            'organization' => $organization,
            'invoice' => $invoice,
        ])->setPaper('a4');

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
