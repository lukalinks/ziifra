<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Support\SpreadsheetExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipExportService
{
    public function exportRunCsv(PayrollRun $run): StreamedResponse
    {
        $run->load('items.employee');

        $headers = [
            __('payroll.export.employee'),
            __('payroll.export.code'),
            __('payroll.export.hours'),
            __('payroll.export.rate'),
            __('payroll.export.gross'),
            __('payroll.export.net'),
        ];

        $rows = $run->items->map(function ($item) {
            return [
                $item->employeeName(),
                $item->employee_snapshot['employee_code'] ?? $item->employee?->displayCode() ?? '',
                $item->hours_worked ?? '',
                $item->hourly_rate ?? '',
                $item->gross_salary,
                $item->net_salary,
            ];
        })->all();

        return SpreadsheetExport::csvDownload(
            'payroll-'.$run->year.'-'.str_pad((string) $run->month, 2, '0', STR_PAD_LEFT).'.csv',
            $headers,
            $rows,
        );
    }
}
