<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollTimeExportService
{
    public function __construct(
        protected PayrollTimeService $payrollTime,
    ) {}

    public function pdf(Organization $organization, int $year, ?int $month, ?int $projectId, ?string $search = null)
    {
        $data = $this->exportData($organization, $year, $month, $projectId, null, $search);

        return Pdf::loadView('app.payroll-time.export-pdf', $data)
            ->download($this->filename($organization, $year, $month, 'pdf'));
    }

    public function excel(Organization $organization, int $year, ?int $month, ?int $projectId, ?string $search = null): StreamedResponse
    {
        $data = $this->exportData($organization, $year, $month, $projectId, null, $search);

        return $this->streamCsv($data['rows'], $data['totals'], $this->filename($organization, $year, $month, 'csv'));
    }

    /**
     * @return array<string, mixed>
     */
    public function exportData(Organization $organization, int $year, ?int $month, ?int $projectId, ?int $employeeId = null, ?string $search = null): array
    {
        return $this->buildExportData($organization, $year, $month, $projectId, $employeeId, $search);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, float>  $totals
     */
    public function csvContent(array $rows, array $totals): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Employee', 'Code', 'Hours', 'Rate/h', 'Gross', 'Trust (employee)', 'Trust (employer)', 'Net']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['employee']->fullName(),
                $row['employee']->displayCode(),
                $row['total_hours'],
                $row['hourly_rate'],
                $row['gross'],
                $row['trust_employee'],
                $row['trust_employer'],
                $row['net'],
            ]);
        }

        fputcsv($handle, ['TOTAL', '', $totals['hours'], '', $totals['gross'], $totals['trust_employee'], $totals['trust_employer'], $totals['net']]);
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content !== false ? $content : '';
    }

    public function employeePdf(Organization $organization, Employee $employee, int $year, ?int $month, ?int $projectId)
    {
        $data = $this->buildExportData($organization, $year, $month, $projectId, $employee->id);

        return Pdf::loadView('app.payroll-time.export-pdf', $data)
            ->download($this->employeeFilename($employee, $year, $month, 'pdf'));
    }

    public function employeeExcel(Organization $organization, Employee $employee, int $year, ?int $month, ?int $projectId): StreamedResponse
    {
        $data = $this->buildExportData($organization, $year, $month, $projectId, $employee->id);

        return $this->streamCsv($data['rows'], $data['totals'], $this->employeeFilename($employee, $year, $month, 'csv'));
    }

    protected function streamCsv(array $rows, array $totals, string $filename): StreamedResponse
    {
        $content = $this->csvContent($rows, $totals);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExportData(Organization $organization, int $year, ?int $month, ?int $projectId, ?int $employeeId = null, ?string $search = null): array
    {
        $settings = $organization->resolvedPayrollSettings();

        $base = [
            'organization' => $organization,
            'project' => null,
            'logo' => ($settings['show_logo'] ?? true) ? $organization->payslipLogoDataUri() : null,
            'trustEmployeePct' => (float) ($settings['trust_employee_percent'] ?? 5),
            'trustEmployerPct' => (float) ($settings['trust_employer_percent'] ?? 5),
            'vatPct' => ($settings['show_vat'] ?? true) ? (float) ($settings['vat_percent'] ?? 8) : 0,
            'payrollSettings' => $settings,
        ];

        $grid = $month !== null
            ? $this->payrollTime->grid($organization, $year, $month, $projectId, $search)
            : $this->payrollTime->yearGrid($organization, $year, $projectId, $search);

        $rows = $this->filterRows($grid['rows'], $employeeId);

        return array_merge($base, [
            'year' => $year,
            'month' => $month,
            'project' => $grid['project'],
            'rows' => $rows,
            'totals' => $this->totalsFor($rows, $grid['totals'], $employeeId),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function filterRows(array $rows, ?int $employeeId): array
    {
        if ($employeeId === null) {
            return $rows;
        }

        return array_values(array_filter($rows, fn ($row) => $row['employee']->id === $employeeId));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, float>  $gridTotals
     * @return array<string, float>
     */
    protected function totalsFor(array $rows, array $gridTotals, ?int $employeeId): array
    {
        if ($employeeId === null) {
            return $gridTotals;
        }

        $totals = ['hours' => 0.0, 'gross' => 0.0, 'trust_employee' => 0.0, 'trust_employer' => 0.0, 'net' => 0.0];

        foreach ($rows as $row) {
            $totals['hours'] += $row['total_hours'];
            $totals['gross'] += $row['gross'];
            $totals['trust_employee'] += $row['trust_employee'];
            $totals['trust_employer'] += $row['trust_employer'];
            $totals['net'] += $row['net'];
        }

        return $totals;
    }

    protected function filename(Organization $organization, int $year, ?int $month, string $ext): string
    {
        $period = $month ? sprintf('%04d-%02d', $year, $month) : (string) $year;

        return "payroll-time-{$organization->slug}-{$period}.{$ext}";
    }

    protected function employeeFilename(Employee $employee, int $year, ?int $month, string $ext): string
    {
        $period = $month ? sprintf('%04d-%02d', $year, $month) : (string) $year;
        $name = \Illuminate\Support\Str::slug($employee->fullName()) ?: 'employee';

        return "payroll-{$name}-{$period}.{$ext}";
    }
}
