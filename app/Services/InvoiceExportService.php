<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Support\SpreadsheetExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportService
{
    public function exportCsv(Invoice $invoice): StreamedResponse
    {
        $headers = [
            __('invoices.export.employee'),
            __('invoices.export.code'),
            __('invoices.export.hours'),
            __('invoices.export.rate'),
            __('invoices.export.amount'),
        ];

        $rows = [];

        foreach ($invoice->line_items ?? [] as $line) {
            $rows[] = [
                $line['employee_name'] ?? '',
                $line['employee_code'] ?? '',
                $line['hours'] ?? '',
                $line['hourly_rate'] ?? '',
                $line['amount'] ?? '',
            ];
        }

        if ($rows === []) {
            $rows[] = [$invoice->title, '', '', '', $invoice->amount];
        }

        return SpreadsheetExport::csvDownload(
            'invoice-'.$invoice->invoice_number.'.csv',
            $headers,
            $rows,
        );
    }
}
