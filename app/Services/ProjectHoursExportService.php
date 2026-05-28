<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectHoursExportService
{
    private const BRAND = '1E3A5F';
    private const BRAND_SOFT = 'EEF2F7';
    private const ACCENT = 'C9A227';
    private const WEEKEND = 'F4F1E8';
    private const TOTAL_ROW = 'E8EDF3';

    public function __construct(
        protected DailyHoursService $hours,
    ) {}

    public function exportCsv(Project $project, Carbon $month): StreamedResponse
    {
        return $this->exportXlsx($project, $month);
    }

    public function exportXlsx(Project $project, Carbon $month): StreamedResponse
    {
        $grid = $this->hours->gridForProject($project, $month);
        $organization = $project->organization;
        $currency = $grid['currency'] ?? ($project->currency ?? 'EUR');
        $days = $grid['days'];
        $dayCount = count($days);
        $monthCarbon = Carbon::parse($grid['month'].'-01');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($monthCarbon->format('M Y'), 0, 31));
        $spreadsheet->getProperties()
            ->setCreator($organization?->name ?? 'ZIIFRA')
            ->setTitle($project->name.' — '.$monthCarbon->format('F Y'))
            ->setSubject(__('daily_hours.tab'));

        // Column layout: A=Employee B=Code C=Rate/h | days... | Total
        $firstDayCol = 4; // D
        $lastDayCol = $firstDayCol + $dayCount - 1;
        $totalCol = $lastDayCol + 1;
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCol);

        // ---- Title block ----
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', strtoupper($organization?->legal_name ?: ($organization?->name ?? 'Company')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::BRAND);

        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', $project->name.'  —  '.__('daily_hours.tab').' · '.$monthCarbon->format('F Y'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('444B59');

        $sheet->mergeCells("A3:{$lastColLetter}3");
        $metaParts = [$project->status->label()];
        if ($project->start_date) {
            $metaParts[] = __('projects.start_date').': '.$project->start_date->format('M j, Y');
        }
        $metaParts[] = __('daily_hours.export_generated').': '.now()->format('M j, Y H:i');
        $sheet->setCellValue('A3', implode('   ·   ', $metaParts));
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('8A93A6');

        $headerRow = 5;
        $firstDataRow = $headerRow + 1;

        // ---- Header row ----
        $sheet->setCellValue("A{$headerRow}", __('daily_hours.export_columns.employee'));
        $sheet->setCellValue("B{$headerRow}", __('daily_hours.export_columns.code'));
        $sheet->setCellValue("C{$headerRow}", __('daily_hours.rate_per_hour'));

        foreach ($days as $i => $day) {
            $col = Coordinate::stringFromColumnIndex($firstDayCol + $i);
            $sheet->setCellValue("{$col}{$headerRow}", $day);
        }
        $sheet->setCellValue("{$lastColLetter}{$headerRow}", __('daily_hours.total'));

        $headerStyle = $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}");
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BRAND);
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $sheet->getStyle("A{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Mark weekend day columns
        $weekendCols = [];
        foreach ($days as $i => $day) {
            $date = Carbon::create($monthCarbon->year, $monthCarbon->month, (int) $day);
            if ($date->isWeekend()) {
                $weekendCols[] = $firstDayCol + $i;
            }
        }

        // ---- Data rows ----
        $row = $firstDataRow;
        $dayTotals = array_fill(0, $dayCount, 0.0);
        $grandHours = 0.0;
        $grandPay = 0.0;

        foreach ($grid['employees'] as $employee) {
            $meta = $grid['rows'][$employee->id] ?? ['rate' => 0, 'pay' => 0];
            $sheet->setCellValue("A{$row}", $employee->fullName());
            $sheet->setCellValue("B{$row}", $employee->displayCode());
            $sheet->setCellValue("C{$row}", (float) ($meta['rate'] ?? 0));

            $rowTotal = 0.0;
            foreach ($days as $i => $day) {
                $entry = $grid['grid'][$employee->id][$day] ?? null;
                $value = $entry ? (float) $entry->hours : 0.0;
                $col = Coordinate::stringFromColumnIndex($firstDayCol + $i);
                if ($value > 0) {
                    $sheet->setCellValue("{$col}{$row}", $value);
                    $rowTotal += $value;
                    $dayTotals[$i] += $value;
                }
            }

            $sheet->setCellValue("{$lastColLetter}{$row}", $rowTotal);
            $grandHours += $rowTotal;
            $grandPay += (float) ($meta['pay'] ?? 0);
            $row++;
        }

        $lastDataRow = $row - 1;

        // ---- Daily total row ----
        $sheet->setCellValue("A{$row}", __('daily_hours.total'));
        $sheet->mergeCells("A{$row}:C{$row}");
        foreach ($days as $i => $day) {
            $col = Coordinate::stringFromColumnIndex($firstDayCol + $i);
            if ($dayTotals[$i] > 0) {
                $sheet->setCellValue("{$col}{$row}", round($dayTotals[$i], 2));
            }
        }
        $sheet->setCellValue("{$lastColLetter}{$row}", round($grandHours, 2));
        $totalStyle = $sheet->getStyle("A{$row}:{$lastColLetter}{$row}");
        $totalStyle->getFont()->setBold(true);
        $totalStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::TOTAL_ROW);
        $totalRowIndex = $row;

        // ---- Number formats & alignment for the matrix ----
        if ($lastDataRow >= $firstDataRow) {
            $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $matrix = Coordinate::stringFromColumnIndex($firstDayCol)."{$firstDataRow}:{$lastColLetter}{$lastDataRow}";
            $sheet->getStyle($matrix)->getNumberFormat()->setFormatCode('0.##');
            $sheet->getStyle($matrix)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDayCol)."{$totalRowIndex}:{$lastColLetter}{$totalRowIndex}")
            ->getNumberFormat()->setFormatCode('0.##');

        // Borders around the table
        $tableRange = "A{$headerRow}:{$lastColLetter}{$totalRowIndex}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D5DBE3');

        // Weekend shading for data rows
        foreach ($weekendCols as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getStyle("{$colLetter}{$firstDataRow}:{$colLetter}{$lastDataRow}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WEEKEND);
        }

        // ---- Summary block ----
        $summaryRow = $totalRowIndex + 2;
        $summary = [
            [__('daily_hours.footer_hours'), round($grandHours, 2).' h'],
            [trans_choice('daily_hours.stat_employees', $grid['employees']->count(), ['count' => $grid['employees']->count()]), (string) $grid['employees']->count()],
            [__('daily_hours.footer_payroll'), $currency.' '.number_format($grandPay, 2)],
            [__('daily_hours.footer_pending'), (string) ($grid['totals']['pending_employees'] ?? 0)],
        ];
        foreach ($summary as $line) {
            $sheet->setCellValue("A{$summaryRow}", $line[0]);
            $sheet->setCellValue("C{$summaryRow}", $line[1]);
            $sheet->getStyle("A{$summaryRow}")->getFont()->getColor()->setRGB('8A93A6');
            $sheet->getStyle("C{$summaryRow}")->getFont()->setBold(true)->getColor()->setRGB(self::BRAND);
            $summaryRow++;
        }

        // ---- Sizing & freeze ----
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        for ($c = $firstDayCol; $c <= $lastDayCol; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(4.6);
        }
        $sheet->getColumnDimension($lastColLetter)->setWidth(9);

        $sheet->freezePane('D'.$firstDataRow);
        $sheet->setSelectedCell('A1');
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $filename = sprintf(
            'hours-%s-%s.xlsx',
            \Illuminate\Support\Str::slug($project->name) ?: $project->id,
            $grid['month'],
        );

        return $this->streamXlsx($spreadsheet, $filename);
    }

    protected function streamXlsx(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
